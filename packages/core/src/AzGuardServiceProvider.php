<?php

namespace AzGuard;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use AzGuard\Guard\PanelManager;
use AzGuard\Guard\Authorizer;
use AzGuard\Guard\DiscoveryService;

class AzGuardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/az-guard.php', 'az-guard');

        $this->app->singleton(DiscoveryService::class, fn() => new DiscoveryService());
        $this->app->singleton(Authorizer::class, fn() => new Authorizer());

        $this->app->singleton(PanelManager::class, function ($app) {
            return new PanelManager($app->make(Authorizer::class));
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
            $this->commands([
                Commands\CreateRoleCommand::class,
            ]);
        }

        // Главный перехватчик всех проверок Gate::allows() / @can
        Gate::before(function ($user, $ability) {
            return app(PanelManager::class)->authorize($user, $ability);
        });
    }
}
