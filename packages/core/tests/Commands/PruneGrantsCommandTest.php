<?php

declare(strict_types=1);

namespace AzGuard\Tests\Commands;

use AzGuard\AzGuardServiceProvider;
use AzGuard\Models\DirectGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

final class PruneGrantsCommandTest extends TestCase
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
    }

    private function seedGrants(): void
    {
        // активные
        DirectGrant::create(['model_type' => 'App\\User', 'model_id' => 1, 'permission_key' => 'x', 'panel_id' => 'app', 'expires_at' => null]);
        DirectGrant::create(['model_type' => 'App\\User', 'model_id' => 1, 'permission_key' => 'y', 'panel_id' => 'app', 'expires_at' => now()->addHour()]);
        // истёкшие
        DirectGrant::create(['model_type' => 'App\\User', 'model_id' => 2, 'permission_key' => 'a', 'panel_id' => 'app',   'expires_at' => now()->subHour()]);
        DirectGrant::create(['model_type' => 'App\\User', 'model_id' => 2, 'permission_key' => 'b', 'panel_id' => 'admin', 'expires_at' => now()->subMinute()]);
    }

    public function test_prune_removes_all_expired_grants(): void
    {
        $this->seedGrants();

        $this->artisan('az-guard:prune-grants')
            ->assertSuccessful()
            ->expectsOutputToContain('2');

        $this->assertDatabaseCount('az_guard_direct_grants', 2);
    }

    public function test_prune_with_panel_removes_only_that_panel(): void
    {
        $this->seedGrants();

        $this->artisan('az-guard:prune-grants', ['--panel' => 'app'])
            ->assertSuccessful()
            ->expectsOutputToContain('1');

        // admin grant остался
        $this->assertDatabaseHas('az_guard_direct_grants', [
            'panel_id' => 'admin',
            'permission_key' => 'b',
        ]);
    }

    public function test_prune_reports_zero_when_nothing_to_delete(): void
    {
        $this->artisan('az-guard:prune-grants')
            ->assertSuccessful()
            ->expectsOutputToContain('0');
    }
}
