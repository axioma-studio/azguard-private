<?php

declare(strict_types=1);

namespace AzGuard;

use AzGuard\Auth\PolicyAttributeRegistrar;
use AzGuard\Commands\DoctorCommand;
use AzGuard\Commands\ListPermissionsCommand;
use AzGuard\Commands\ListScopedRolesCommand;
use AzGuard\Commands\CacheResetCommand;
use AzGuard\Commands\SyncRolesCommand;
use AzGuard\Commands\MakeGuardAbilitiesCommand;
use AzGuard\Commands\MakeGuardPanelCommand;
use AzGuard\Commands\MakeGuardPermissionCommand;
use AzGuard\Commands\MakeGuardPolicyCommand;
use AzGuard\Commands\MakeGuardRoleCommand;
use AzGuard\Commands\GrantCommand;
use AzGuard\Commands\RevokeGrantCommand;
use AzGuard\Commands\PruneGrantsCommand;
use AzGuard\Contracts\AzGuardManagerInterface;
use AzGuard\Guard\GuardDoctor;
use AzGuard\Guard\Authorizer;
use AzGuard\Http\Middleware\CheckAccess;
use AzGuard\Http\Middleware\CheckDirectGrant;
use AzGuard\Http\Middleware\LoadAzGuardRoles;
use AzGuard\Http\Middleware\SetCurrentPanel;
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

        $this->registerPanelProviders();
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(paths: __DIR__ . '/../database/migrations');

        Gate::before(function ($user, string $ability): ?bool {
            if ($user === null || ! method_exists($user, 'getAzPermissions')) {
                return null;
            }

            return app(Authorizer::class)->check(user: $user, ability: $ability);
        });

        $this->registerMiddlewareAliases();
        $this->registerBladeDirectives();

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
                // Phase 7 — Grants CLI
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
     * Регистрирует Blade-директивы: @azcan, @endazcan, @azrole, @endazrole.
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
