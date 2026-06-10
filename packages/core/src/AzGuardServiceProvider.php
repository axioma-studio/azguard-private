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
use AzGuard\Contracts\PermissionResolverInterface;
use AzGuard\Guard\Authorizer;
use AzGuard\Guard\AzGuardDiagnostics;
use AzGuard\Guard\GuardDoctor;
use AzGuard\Http\Middleware\CheckAccess;
use AzGuard\Http\Middleware\CheckDirectGrant;
use AzGuard\Http\Middleware\LoadAzGuardRoles;
use AzGuard\Http\Middleware\SetCurrentPanel;
use AzGuard\Registry\Builders\CompositePermissionCatalog;
use AzGuard\Registry\Contracts\PermissionCatalog;
use AzGuard\Registry\Resolver\EffectivePermissionResolver;
use AzGuard\Registry\Resolver\PermissionCache;
use AzGuard\Registry\Sources\ClassRoleGrantSource;
use AzGuard\Registry\Sources\DatabaseRoleGrantSource;
use AzGuard\Registry\Sources\DirectGrantSource;
use AzGuard\Support\Config;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Override;

final class AzGuardServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(
            path: __DIR__.'/../config/az-guard.php',
            key: 'az-guard',
        );

        $this->app->singleton(AzGuardManager::class);
        $this->app->bind(AzGuardManagerInterface::class, AzGuardManager::class);

        $this->app->singleton(PolicyAttributeRegistrar::class);

        // Canonical binding — use AzGuardDiagnostics going forward.
        $this->app->singleton(AzGuardDiagnostics::class);
        // BC alias — resolving the old GuardDoctor name from the container still works.
        $this->app->alias(AzGuardDiagnostics::class, GuardDoctor::class);

        // ─── Registry ─────────────────────────────────────────────────────────

        $this->app->singleton(ClassRoleGrantSource::class);
        $this->app->singleton(DatabaseRoleGrantSource::class);
        $this->app->singleton(DirectGrantSource::class);

        $this->app->tag([
            ClassRoleGrantSource::class,
            DatabaseRoleGrantSource::class,
            DirectGrantSource::class,
        ], 'azguard.grant_sources');

        $this->app->singleton(PermissionCache::class);

        $this->app->singleton(EffectivePermissionResolver::class, function (): EffectivePermissionResolver {
            $allSources = iterator_to_array($this->app->tagged('azguard.grant_sources'), preserve_keys: false);
            $allowlist = Config::grantSources();

            $sources = $allowlist !== null
                ? array_filter($allSources, static fn (object $s): bool => in_array($s::class, $allowlist, strict: true))
                : $allSources;

            return new EffectivePermissionResolver(
                catalog: $this->app->make(PermissionCatalog::class),
                sources: $sources,
                cache: $this->app->make(PermissionCache::class),
            );
        });

        $this->app->bind(PermissionResolverInterface::class, EffectivePermissionResolver::class);

        $this->registerPanelProviders();
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(paths: __DIR__.'/../database/migrations');

        $this->app->singleton(PermissionCatalog::class, function (): PermissionCatalog {
            /** @var AzGuardManager $manager */
            $manager = $this->app->make(AzGuardManager::class);
            $panelIds = array_keys($manager->getPanels());

            $builders = iterator_to_array(
                $this->app->tagged('azguard.catalog_builders'),
                preserve_keys: false,
            );

            return new CompositePermissionCatalog(
                builders: $builders,
                panelIds: $panelIds,
            );
        });

        // Use instanceof instead of method_exists for a precise type check.
        Gate::before(function ($user, string $ability): ?bool {
            if (! $user instanceof Authenticatable) {
                return null;
            }

            return app(Authorizer::class)->check(user: $user, ability: $ability);
        });

        Gate::define('direct-grant', [DirectGrantPolicy::class, 'check']);

        $this->registerMiddlewareAliases();
        $this->registerBladeDirectives();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/az-guard.php' => config_path('az-guard.php'),
            ], 'az-guard-config');

            $this->commands([
                DoctorCommand::class,
                CatalogListCommand::class,
                CatalogValidateCommand::class,
                RolePermissionsCommand::class,
                MakeGuardPanelCommand::class,
                MakeGuardPermissionCommand::class,
                MakeGuardPolicyCommand::class,
                MakeGuardAbilitiesCommand::class,
                MakeGuardRoleCommand::class,
                ListPermissionsCommand::class,
                ListScopedRolesCommand::class,
                GrantsListCommand::class,
                SyncRolesCommand::class,
                CacheResetCommand::class,
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

        $router->aliasMiddleware('azguard.roles', LoadAzGuardRoles::class);
        $router->aliasMiddleware('azguard.panel', SetCurrentPanel::class);
        $router->aliasMiddleware('azguard.check', CheckAccess::class);
        $router->aliasMiddleware('azguard.grant', CheckDirectGrant::class);

        $alias = Config::checkAccessAlias();

        if ($alias !== 'azguard.check') {
            $router->aliasMiddleware($alias, CheckAccess::class);
        }
    }

    /**
     * Blade directives.
     *
     * @azcan    / @endazcan    — permission check
     *
     * @elseazcan / @unlessazcan / @endunlessazcan — added in DX2
     *
     * @azrole   / @endazrole   — role check
     *
     * @azdirect / @endazdirect — direct grant check
     */
    protected function registerBladeDirectives(): void
    {
        Blade::directive('azcan', fn (string $expression): string => "<?php if (\\AzGuard\\Support\\BladeHelper::authed() && auth()->user()->hasPermission({$expression})): ?>");

        Blade::directive('endazcan', fn (): string => '<?php endif; ?>');

        Blade::directive('elseazcan', fn (string $expression): string => "<?php elseif (\\AzGuard\\Support\\BladeHelper::authed() && auth()->user()->hasPermission({$expression})): ?>");

        Blade::directive('unlessazcan', fn (string $expression): string => "<?php if (! \\AzGuard\\Support\\BladeHelper::authed() || ! auth()->user()->hasPermission({$expression})): ?>");

        Blade::directive('endunlessazcan', fn (): string => '<?php endif; ?>');

        Blade::directive('azrole', fn (string $expression): string => "<?php if (\\AzGuard\\Support\\BladeHelper::authed() && auth()->user()->hasRole({$expression})): ?>");

        Blade::directive('endazrole', fn (): string => '<?php endif; ?>');

        Blade::directive('azdirect', fn (string $expression): string => "<?php if (\\AzGuard\\Support\\BladeHelper::authed() && method_exists(auth()->user(), 'hasDirectGrant') && auth()->user()->hasDirectGrant({$expression})): ?>");

        Blade::directive('endazdirect', fn (): string => '<?php endif; ?>');
    }

    protected function registerPanelProviders(): void
    {
        foreach (Config::panels() as $provider) {
            if (is_string($provider) && class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }
}
