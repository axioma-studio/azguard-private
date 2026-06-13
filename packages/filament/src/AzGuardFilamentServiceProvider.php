<?php

declare(strict_types=1);

namespace AzGuard\Filament;

use AzGuard\Filament\Commands\GenerateFilamentPermissionsCommand;
use AzGuard\Filament\Permissions\FilamentDiscovery;
use AzGuard\Filament\Permissions\FilamentPermissionCatalogBuilder;
use AzGuard\Filament\Permissions\PermissionDiscovery;
use AzGuard\Filament\Permissions\PermissionSchema;
use AzGuard\Filament\Permissions\ResourceGate;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Override;

final class AzGuardFilamentServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/az-guard-filament.php', 'az-guard-filament');

        $this->app->singleton(PermissionDiscovery::class, function (): FilamentDiscovery {
            /** @var array{abilities?: list<string>, pages?: array{ability?: string}, exclude?: array<string, list<string>>} $config */
            $config = config('az-guard-filament');

            return new FilamentDiscovery(
                abilities: $config['abilities'] ?? [],
                pageAbility: $config['pages']['ability'] ?? 'view',
                exclude: $config['exclude'] ?? [],
            );
        });

        $this->app->singleton(
            PermissionSchema::class,
            fn (): PermissionSchema => PermissionSchema::fromConfig((array) config('az-guard-filament')),
        );

        $this->app->singleton(FilamentPermissionCatalogBuilder::class, fn (Application $app): FilamentPermissionCatalogBuilder => new FilamentPermissionCatalogBuilder(
            panelId: (string) config('az-guard-filament.panel', 'admin'),
            schema: $app->make(PermissionSchema::class),
            discovery: $app->make(PermissionDiscovery::class),
        ));

        // The "database" source feeds the catalog at runtime; "enum"/"policy"
        // sources register through their own generated files instead.
        if (config('az-guard-filament.source', 'database') === 'database') {
            $this->app->tag([FilamentPermissionCatalogBuilder::class], 'azguard.catalog_builders');
        }

        $this->app->singleton(ResourceGate::class, fn (Application $app): ResourceGate => new ResourceGate(
            panelId: (string) config('az-guard-filament.panel', 'admin'),
            schema: $app->make(PermissionSchema::class),
            discovery: $app->make(PermissionDiscovery::class),
        ));
    }

    public function boot(): void
    {
        $viewsPath = __DIR__.'/../resources/views';

        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'az-guard');
        }

        if (config('az-guard-filament.enforce', true)) {
            $gate = $this->app->make(ResourceGate::class);

            Gate::before(fn ($user, string $ability, array $arguments = []): ?bool => is_object($user)
                ? $gate->check($user, $ability, $arguments)
                : null);
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/az-guard-filament.php' => config_path('az-guard-filament.php'),
            ], 'az-guard-filament-config');

            if (is_dir($viewsPath)) {
                $this->publishes([
                    $viewsPath => resource_path('views/vendor/az-guard'),
                ], 'az-guard-views');
            }

            $this->commands([
                GenerateFilamentPermissionsCommand::class,
            ]);
        }
    }
}
