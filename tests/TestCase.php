<?php

declare(strict_types=1);

namespace AzGuard\Tests;

use AzGuard\AzGuardServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            AzGuardServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('auth.providers.users.model', \AzGuard\Tests\Stubs\User::class);

        $app['config']->set('az-guard.panels', [
            \AzGuard\Tests\Stubs\TestGuardPanelProvider::class,
        ]);

        // Всегда используем in-memory кэш в тестах
        $app['config']->set('az-guard.cache.store', 'array');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../packages/core/database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
