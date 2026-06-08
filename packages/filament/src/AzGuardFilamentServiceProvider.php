<?php

declare(strict_types=1);

namespace AzGuard\Filament;

use Illuminate\Support\ServiceProvider;

final class AzGuardFilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $viewsPath = __DIR__ . '/../resources/views';

        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'az-guard');

            if ($this->app->runningInConsole()) {
                $this->publishes([
                    $viewsPath => resource_path('views/vendor/az-guard'),
                ], 'az-guard-views');
            }
        }
    }
}
