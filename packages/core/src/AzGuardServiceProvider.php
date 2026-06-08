<?php

declare(strict_types=1);

namespace AzGuard;

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
use AzGuard\Commands\RevokeCommand;
use AzGuard\Commands\RolePermissionsCommand;
use AzGuard\Commands\SyncRolesCommand;
use AzGuard\Contracts\AzGuardManagerInterface;
use AzGuard\Guard\GuardDoctor;
use AzGuard\Guard\Authorizer;
use AzGuard\Http\Middleware\CheckAccess;
use AzGuard\Http\Middleware\LoadAzGuardRoles;
use AzGuard\Http\Middleware\SetCurrentPanel;
use AzGuard\Registry\Builders\CompositePermissionCatalog;
use AzGuard\Registry\Builders\EnumPermissionCatalogBuilder;
use AzGuard\Registry\Builders\PolicyAbilityCatalogBuilder;
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

        $this->registerRegistryBindings();
        $this->registerPanelProviders();
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(paths: __DIR__ . '/../database/migrations');

        Gate::before(function ($user, string $ability): ?bool {
            if ($user === null || ! method_exists($user, 'getAzPermissionSet')) {
                return null;
            }

            return app(Authorizer::class)->check(user: $user, ability: $ability);
        });

        $this->registerMiddlewareAliases();
        $this->registerBladeDirectives();
        $this->registerOctaneListeners();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/az-guard.php' => config_path('az-guard.php'),
            ], 'az-guard-config');

            $this->commands([
                DoctorCommand::class,
                MakeGuardPanelCommand::class,
                MakeGuardPermissionCommand::class,
                MakeGuardPolicyCommand::class,
                MakeGuardAbilitiesCommand::class,
                MakeGuardRoleCommand::class,
                ListPermissionsCommand::class,
                ListScopedRolesCommand::class,
                CacheResetCommand::class,
                SyncRolesCommand::class,
                CatalogListCommand::class,
                CatalogValidateCommand::class,
                // Phase 4: grants CLI
                GrantCommand::class,
                RevokeCommand::class,
                GrantsListCommand::class,
                RolePermissionsCommand::class,
            ]);
        }
    }

    /**
     * Привязка компонентов Registry.
     *
     * Источники grants (в порядке приоритета, desc):
     *   100 — ClassRoleGrantSource   (Фаза 1: PHP-классы ролей)
     *    90 — DatabaseRoleGrantSource (Фаза 3: DB-роли без class_name)
     *    80 — DirectGrantSource       (Фаза 3: прямые гранты пользователю)
     */
    protected function registerRegistryBindings(): void
    {
        $this->app->singleton(PermissionResolverCache::class, fn (): PermissionResolverCache => new PermissionResolverCache);

        $this->app->singleton(ClassRoleGrantSource::class, fn (): ClassRoleGrantSource => new ClassRoleGrantSource);
        $this->app->singleton(DatabaseRoleGrantSource::class, fn (): DatabaseRoleGrantSource => new DatabaseRoleGrantSource);
        $this->app->singleton(DirectGrantSource::class, fn (): DirectGrantSource => new DirectGrantSource);

        $this->app->singleton(PermissionCatalog::class, function (): PermissionCatalog {
            /** @var AzGuardManager $manager */
            $manager = $this->app->make(AzGuardManager::class);

            $panelIds = array_keys($manager->getPanels());

            $builders = [
                new EnumPermissionCatalogBuilder,
                new PolicyAbilityCatalogBuilder,
            ];

            return new CompositePermissionCatalog(
                builders: $builders,
                panelIds: $panelIds,
            );
        });

        $this->app->singleton(
            EffectivePermissionResolver::class,
            function (): EffectivePermissionResolver {
                return new EffectivePermissionResolver(
                    catalog: $this->app->make(PermissionCatalog::class),
                    sources: [
                        $this->app->make(ClassRoleGrantSource::class),
                        $this->app->make(DatabaseRoleGrantSource::class),
                        $this->app->make(DirectGrantSource::class),
                    ],
                    cache: $this->app->make(PermissionResolverCache::class),
                );
            }
        );

        $this->app->tag(
            [
                ClassRoleGrantSource::class,
                DatabaseRoleGrantSource::class,
                DirectGrantSource::class,
            ],
            [GrantSource::class],
        );
    }

    /**
     * Octane: сбрасываем per-request кэш после каждого запроса.
     */
    protected function registerOctaneListeners(): void
    {
        if (! class_exists('Laravel\\Octane\\Events\\RequestHandled')) {
            return;
        }

        $this->app['events']->listen(
            'Laravel\\Octane\\Events\\RequestHandled',
            function (): void {
                $this->app->make(PermissionResolverCache::class)->forgetAll();
            }
        );
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

        $checkAccessAlias = (string) config(key: 'az-guard.middleware.check_access_alias', default: 'check.access');

        if ($checkAccessAlias !== 'azguard.check') {
            $router->aliasMiddleware($checkAccessAlias, CheckAccess::class);
        }
    }

    protected function registerBladeDirectives(): void
    {
        Blade::directive('azcan', function (string $expression): string {
            return "<?php if (auth()->check() && auth()->user()->checkAzPermission({$expression})): ?>";
        });

        Blade::directive('endazcan', fn (): string => '<?php endif; ?>');

        Blade::directive('azrole', function (string $expression): string {
            return "<?php if (auth()->check() && auth()->user()->hasAzRole({$expression})): ?>";
        });

        Blade::directive('endazrole', fn (): string => '<?php endif; ?>');
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
