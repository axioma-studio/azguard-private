<?php

namespace AzGuard;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AzGuardServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/az-guard.php', 'az-guard');
        $this->app->singleton(Guard\Authorizer::class, fn () => new Guard\Authorizer);
        $this->app->singleton(Guard\PanelManager::class, fn ($app) => new Guard\PanelManager($app->make(Guard\Authorizer::class)));
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
            $this->commands([Commands\CreateRoleCommand::class]);
        }
        Gate::before(fn ($user, $ability) => app(Guard\PanelManager::class)->authorize($user, $ability));
    }
}
