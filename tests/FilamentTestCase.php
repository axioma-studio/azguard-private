<?php

declare(strict_types=1);

namespace AzGuard\Tests;

use AzGuard\Filament\AzGuardFilamentServiceProvider;
use AzGuard\Filament\Permissions\PermissionDiscovery;
use AzGuard\Filament\Permissions\PermissionSubject;
use AzGuard\Tests\Stubs\AdminPanelProvider;
use AzGuard\Tests\Stubs\Project;

/**
 * TestCase wiring the Filament package against an 'admin' panel whose resource
 * permissions are produced by a fixed discovery (one Project-backed resource),
 * so the database-source catalog + enforcement can be exercised without
 * bootstrapping a real Filament panel.
 */
class FilamentTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AzGuardFilamentServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('az-guard.panels', [AdminPanelProvider::class]);
        $app['config']->set('az-guard-filament.panel', 'admin');
        $app['config']->set('az-guard-filament.source', 'database');
        $app['config']->set('az-guard-filament.abilities', ['view_any', 'view', 'create']);

        $app->singleton(PermissionDiscovery::class, fn (): PermissionDiscovery => new class implements PermissionDiscovery
        {
            public function subjects(string $panelId): array
            {
                return [new PermissionSubject('Project', 'Projects', ['view_any', 'view', 'create'], Project::class)];
            }
        });
    }
}
