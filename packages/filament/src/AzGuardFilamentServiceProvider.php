<?php

declare(strict_types=1);

namespace AzGuard\Filament;

use Illuminate\Support\ServiceProvider;

final class AzGuardFilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(
            path: __DIR__ . '/../resources/views',
            namespace: 'az-guard',
        );

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/az-guard'),
            ], 'az-guard-views');
        }
    }
}
