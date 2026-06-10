<?php

declare(strict_types=1);

namespace AzGuard\Tests;

use AzGuard\AzGuardServiceProvider;
use AzGuard\Tests\Stubs\TestGuardPanelProvider;
use AzGuard\Tests\Stubs\User;
use AzGuard\Tests\Stubs\UserWithDirectGrants;
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
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('app.debug', true);

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('auth.providers.users.model', User::class);

        $app['config']->set('az-guard.panels', [
            TestGuardPanelProvider::class,
        ]);

        $app['config']->set('az-guard.cache.store', 'array');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function createUserWithDirectGrant(string $permission, string $panelId): UserWithDirectGrants
    {
        $user = UserWithDirectGrants::factory()->create();
        $user->directGrants()->create([
            'panel_id' => $panelId,
            'permission_key' => $permission,
            'expires_at' => null,
        ]);

        return $user;
    }
}
