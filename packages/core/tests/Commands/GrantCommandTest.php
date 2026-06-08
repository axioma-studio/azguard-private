<?php

declare(strict_types=1);

namespace AzGuard\Tests\Commands;

use AzGuard\AzGuardServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

final class GrantCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [AzGuardServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('az-guard.table_names', [
            'roles'            => 'az_guard_roles',
            'model_has_roles'  => 'az_guard_model_has_roles',
            'model_has_scopes' => 'az_guard_model_has_scopes',
            'role_permissions' => 'az_guard_role_permissions',
            'direct_grants'    => 'az_guard_direct_grants',
        ]);
        $app['config']->set('auth.providers.users.model', 'App\\Models\\User');
    }

    public function test_grant_creates_record(): void
    {
        $this->artisan('guard:grant', [
            'user_id'        => '42',
            'permission_key' => 'app.documents.view',
            '--panel'        => 'app',
            '--model'        => 'App\\Models\\User',
        ])->assertSuccessful()
          ->expectsOutputToContain('Grant выдан');

        $this->assertDatabaseHas('az_guard_direct_grants', [
            'model_type'     => 'App\\Models\\User',
            'model_id'       => 42,
            'permission_key' => 'app.documents.view',
            'panel_id'       => 'app',
            'expires_at'     => null,
        ]);
    }

    public function test_grant_with_ttl_sets_expires_at(): void
    {
        $before = now()->addSeconds(3590);
        $after  = now()->addSeconds(3610);

        $this->artisan('guard:grant', [
            'user_id'        => '99',
            'permission_key' => 'app.x.edit',
            '--panel'        => 'app',
            '--model'        => 'App\\Models\\User',
            '--ttl'          => '3600',
        ])->assertSuccessful();

        $grant = \AzGuard\Models\DirectGrant::where('model_id', 99)->first();
        $this->assertNotNull($grant);
        $this->assertTrue($grant->expires_at->between($before, $after));
    }

    public function test_grant_updates_existing(): void
    {
        // Первый вызов — бессрочный
        $this->artisan('guard:grant', [
            'user_id'        => '7',
            'permission_key' => 'app.docs.view',
            '--panel'        => 'app',
            '--model'        => 'App\\Models\\User',
        ])->assertSuccessful();

        // Второй вызов — с TTL (обновляет)
        $this->artisan('guard:grant', [
            'user_id'        => '7',
            'permission_key' => 'app.docs.view',
            '--panel'        => 'app',
            '--model'        => 'App\\Models\\User',
            '--ttl'          => '7200',
        ])->assertSuccessful()
          ->expectsOutputToContain('Grant обновлён');

        $this->assertDatabaseCount('az_guard_direct_grants', 1);
    }
}
