<?php

declare(strict_types=1);

namespace AzGuard\Tests\Commands;

use AzGuard\AzGuardServiceProvider;
use AzGuard\Models\DirectGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

final class RevokeCommandTest extends TestCase
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
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('az-guard.table_names', [
            'roles' => 'az_guard_roles',
            'model_has_roles' => 'az_guard_model_has_roles',
            'model_has_scopes' => 'az_guard_model_has_scopes',
            'role_permissions' => 'az_guard_role_permissions',
            'direct_grants' => 'az_guard_direct_grants',
        ]);
        $app['config']->set('auth.providers.users.model', 'App\\Models\\User');
    }

    private function seedGrant(int $userId, string $key, string $panel = 'app'): void
    {
        DirectGrant::create([
            'model_type' => 'App\\Models\\User',
            'model_id' => $userId,
            'permission_key' => $key,
            'panel_id' => $panel,
            'expires_at' => null,
        ]);
    }

    public function test_revoke_specific_key(): void
    {
        $this->seedGrant(1, 'app.docs.view');
        $this->seedGrant(1, 'app.docs.edit');

        $this->artisan('guard:revoke', [
            'user_id' => '1',
            'permission_key' => 'app.docs.view',
            '--panel' => 'app',
            '--model' => 'App\\Models\\User',
            '--force' => true,
        ])->assertSuccessful()
            ->expectsOutputToContain('Отозвано 1');

        $this->assertDatabaseMissing('az_guard_direct_grants', [
            'model_id' => 1,
            'permission_key' => 'app.docs.view',
        ]);
        $this->assertDatabaseHas('az_guard_direct_grants', [
            'model_id' => 1,
            'permission_key' => 'app.docs.edit',
        ]);
    }

    public function test_revoke_warns_when_not_found(): void
    {
        $this->artisan('guard:revoke', [
            'user_id' => '999',
            'permission_key' => 'app.nothing',
            '--panel' => 'app',
            '--model' => 'App\\Models\\User',
            '--force' => true,
        ])->assertSuccessful()
            ->expectsOutputToContain('Грантов не найдено');
    }

    public function test_revoke_all_for_panel(): void
    {
        $this->seedGrant(5, 'app.a.view');
        $this->seedGrant(5, 'app.b.edit');
        $this->seedGrant(5, 'admin.c.view', 'admin');

        $this->artisan('guard:revoke', [
            'user_id' => '5',
            '--all' => true,
            '--panel' => 'app',
            '--model' => 'App\\Models\\User',
            '--force' => true,
        ])->assertSuccessful()
            ->expectsOutputToContain('Отозвано 2');

        // admin-грант должен остаться
        $this->assertDatabaseHas('az_guard_direct_grants', [
            'model_id' => 5,
            'panel_id' => 'admin',
        ]);
    }
}
