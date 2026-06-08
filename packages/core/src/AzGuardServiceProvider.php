<?php

declare(strict_types=1);

namespace AzGuard;

use AzGuard\Auth\DirectGrantPolicy;
use AzGuard\Auth\PolicyAttributeRegistrar;
use AzGuard\Commands\CacheResetCommand;
use AzGuard\Commands\CatalogListCommand;
use AzGuard\Commands\CatalogValidateCommand;
use AzGuard\Commands\DoctorCommand;
use AzGuard\Commands\GrantCommand;
use AzGuard\Commands\GrantsListCommand;
use AzGuard\Commands\ListPermissionsCommand;
use AzGuard\Commands\ListScopedRolesCommand;
use AzGuard\Commands\MakeGuardAbilitiesCommand;
use AzGuard\Commands\MakeGuardPanelCommand;
use AzGuard\Commands\MakeGuardPermissionCommand;
use AzGuard\Commands\MakeGuardPolicyCommand;
use AzGuard\Commands\MakeGuardRoleCommand;
use AzGuard\Commands\PruneGrantsCommand;
use AzGuard\Commands\RevokeGrantCommand;
use AzGuard\Commands\RolePermissionsCommand;
use AzGuard\Commands\SyncRolesCommand;
use AzGuard\Contracts\AzGuardManagerInterface;
use AzGuard\Guard\Authorizer;
use AzGuard\Guard\GuardDoctor;
use AzGuard\Http\Middleware\CheckAccess;
use AzGuard\Http\Middleware\CheckDirectGrant;
use AzGuard\Http\Middleware\LoadAzGuardRoles;
use AzGuard\Http\Middleware\SetCurrentPanel;
use AzGuard\Registry\Builders\CompositePermissionCatalog;
use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Contracts\PermissionCatalog;
use AzGuard\Registry\Resolver\EffectivePermissionResolver;
use AzGuard\Registry\Resolver\PermissionResolverCache;
use AzGuard\Registry\Sources\ClassRoleGrantSource;
use AzGuard\Registry\Sources\DatabaseRoleGrantSource;
use AzGuard\Registry\Sources\DirectGrantSource;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AzGuardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            path: __DIR__ . '/../config/az-guard.php',
            key: 'az-guard',
        );

        $this->app->singleton(AzGuardManager::class, fn (): AzGuardManager => new AzGuardManager);
        $this->app->bind(AzGuardManagerInterface::class, AzGuardManager::class);

        $this->app->singleton(PolicyAttributeRegistrar::class, fn (): PolicyAttributeRegistrar => new PolicyAttributeRegistrar);
        $this->app->singleton(GuardDoctor::class, fn (): GuardDoctor => new GuardDoctor);

        // ─── Registry ───────────────────────────────────────────────────────────

        // Grant sources — tagged so PanelProviders and packages can add more via tag.
        // Priority: ClassRoleGrantSource=100, DatabaseRoleGrantSource=90, DirectGrantSource=80.
        $this->app->singleton(ClassRoleGrantSource::class);
        $this->app->singleton(DatabaseRoleGrantSource::class);
        $this->app->singleton(DirectGrantSource::class);

        $this->app->tag([
            ClassRoleGrantSource::class,
            DatabaseRoleGrantSource::class,
            DirectGrantSource::class,
        ], 'azguard.grant_sources');

        // PermissionResolverCache — request-scoped, but registered as singleton
        // because it manages its own per-request lifecycle via a request store.
        $this->app->singleton(PermissionResolverCache::class);

        // CompositePermissionCatalog is assembled lazily from builders registered
        // by each PanelProvider. We defer creation until after all providers boot.
        $this->app->singleton(PermissionCatalog::class, function (): PermissionCatalog {
            /** @var AzGuardManager $manager */
            $manager = $this->app->make(AzGuardManager::class);
            $panelIds = array_keys($manager->getPanels());

            // Builders are tagged by PanelProvider::registerCatalogBuilders().
            $builders = iterator_to_array(
                $this->app->tagged('azguard.catalog_builders'),
                preserve_keys: false,
            );

            return new CompositePermissionCatalog(
                builders: $builders,
                panelIds: $panelIds,
            );
        });

        // EffectivePermissionResolver receives catalog + all tagged grant sources.
        $this->app->singleton(EffectivePermissionResolver::class, function (): EffectivePermissionResolver {
            return new EffectivePermissionResolver(
                catalog: $this->app->make(PermissionCatalog::class),
                sources: $this->app->tagged('azguard.grant_sources'),
                cache:   $this->app->make(PermissionResolverCache::class),
            );
        });

        $this->registerPanelProviders();
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(paths: __DIR__ . '/../database/migrations');

        Gate::before(function ($user, string $ability): ?bool {
            if ($user === null || ! method_exists($user, 'getAuthIdentifier')) {
                return null;
            }

            return app(Authorizer::class)->check(user: $user, ability: $ability);
        });

        // Gate-абилити для direct grants
        Gate::define('direct-grant', [DirectGrantPolicy::class, 'check']);

        $this->registerMiddlewareAliases();
        $this->registerBladeDirectives();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/az-guard.php' => config_path('az-guard.php'),
            ], 'az-guard-config');

            $this->commands([
                // Diagnostics
                DoctorCommand::class,
                CatalogListCommand::class,
                CatalogValidateCommand::class,
                RolePermissionsCommand::class,
                // Scaffolding
                MakeGuardPanelCommand::class,
                MakeGuardPermissionCommand::class,
                MakeGuardPolicyCommand::class,
                MakeGuardAbilitiesCommand::class,
                MakeGuardRoleCommand::class,
                // Roles & permissions inspection
                ListPermissionsCommand::class,
                ListScopedRolesCommand::class,
                GrantsListCommand::class,
                SyncRolesCommand::class,
                CacheResetCommand::class,
                // Grants management
                GrantCommand::class,
                RevokeGrantCommand::class,
                PruneGrantsCommand::class,
            ]);
        }
    }

    protected function registerMiddlewareAliases(): void
    {
        $router = $this->app->make(Router::class);

        if (! $router instanceof Router) {
            return;
        }

        $router->aliasMiddleware('azguard.roles',  LoadAzGuardRoles::class);
        $router->aliasMiddleware('azguard.panel',  SetCurrentPanel::class);
        $router->aliasMiddleware('azguard.check',  CheckAccess::class);
        $router->aliasMiddleware('az.grant',        CheckDirectGrant::class);

        $checkAccessAlias = (string) config(key: 'az-guard.middleware.check_access_alias', default: 'check.access');

        if ($checkAccessAlias !== 'azguard.check') {
            $router->aliasMiddleware($checkAccessAlias, CheckAccess::class);
        }
    }

    /**
     * Регистрирует Blade-директивы.
     *
     * @azcan   / @endazcan   — проверка через роли
     * @azrole  / @endazrole  — проверка наличия роли
     * @azdirect / @endazdirect — проверка direct grant
     */
    protected function registerBladeDirectives(): void
    {
        Blade::directive('azcan', function (string $expression): string {
            return "<?php if (auth()->check() && auth()->user()->hasAzPermission({$expression})): ?>";
        });

        Blade::directive('endazcan', fn (): string => '<?php endif; ?>');

        Blade::directive('azrole', function (string $expression): string {
            return "<?php if (auth()->check() && auth()->user()->hasAzRole({$expression})): ?>";
        });

        Blade::directive('endazrole', fn (): string => '<?php endif; ?>');

        // @azdirect('app.documents.export')         — текущая панель
        // @azdirect('app.documents.export', 'app')  — явная панель
        Blade::directive('azdirect', function (string $expression): string {
            return "<?php if (auth()->check() && method_exists(auth()->user(), 'hasDirectGrant') && auth()->user()->hasDirectGrant({$expression})): ?>";
        });

        Blade::directive('endazdirect', fn (): string => '<?php endif; ?>');
    }

    protected function registerPanelProviders(): void
    {
        $providers = config(key: 'az-guard.panels', default: []);

        foreach ($providers as $provider) {
            if (is_string($provider) && class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }
}
