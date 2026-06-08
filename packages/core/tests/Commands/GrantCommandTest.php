<?php

declare(strict_types=1);

namespace AzGuard\Tests\Commands;

use AzGuard\AzGuardServiceProvider;
use AzGuard\Models\DirectGrant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

/**
 * Минимальная Eloquent-модель для командных тестов.
 */
class StubCommandUser extends Model
{
    protected $table      = 'stub_cmd_users';
    protected $fillable   = ['id'];
    public    $timestamps = false;
    public function getAuthIdentifier(): mixed { return $this->getKey(); }
}

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
        // Указываем на стаб-модель
        $app['config']->set('auth.providers.users.model', StubCommandUser::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['db']->statement(
            'CREATE TABLE IF NOT EXISTS stub_cmd_users (id INTEGER PRIMARY KEY AUTOINCREMENT)'
        );
    }

    private function createUser(): StubCommandUser
    {
        return StubCommandUser::create([]);
    }

    // ------------------------------------------------------------------

    public function test_grant_command_creates_grant(): void
    {
        $user = $this->createUser();

        $this->artisan('az-guard:grant', [
            'user-id'    => $user->getKey(),
            'permission' => 'app.docs.view',
            'panel'      => 'app',
        ])->assertSuccessful();

        $this->assertDatabaseHas('az_guard_direct_grants', [
            'model_id'       => $user->getKey(),
            'permission_key' => 'app.docs.view',
            'panel_id'       => 'app',
            'expires_at'     => null,
        ]);
    }

    public function test_grant_command_with_ttl_sets_expires_at(): void
    {
        $user   = $this->createUser();
        $before = now()->addSeconds(3590);
        $after  = now()->addSeconds(3610);

        $this->artisan('az-guard:grant', [
            'user-id'    => $user->getKey(),
            'permission' => 'app.docs.export',
            'panel'      => 'app',
            '--ttl'      => '3600',
        ])->assertSuccessful();

        $grant = DirectGrant::first();
        $this->assertNotNull($grant->expires_at);
        $this->assertTrue($grant->expires_at->between($before, $after));
    }

    public function test_grant_command_fails_when_user_not_found(): void
    {
        $this->artisan('az-guard:grant', [
            'user-id'    => 9999,
            'permission' => 'app.x',
            'panel'      => 'app',
        ])->assertFailed();

        $this->assertDatabaseCount('az_guard_direct_grants', 0);
    }

    public function test_revoke_command_removes_grant(): void
    {
        $user = $this->createUser();

        $this->artisan('az-guard:grant', [
            'user-id'    => $user->getKey(),
            'permission' => 'app.y.edit',
            'panel'      => 'app',
        ])->assertSuccessful();

        $this->artisan('az-guard:revoke-grant', [
            'user-id'    => $user->getKey(),
            'permission' => 'app.y.edit',
            'panel'      => 'app',
            '--force'    => true,
        ])->assertSuccessful();

        $this->assertDatabaseMissing('az_guard_direct_grants', [
            'permission_key' => 'app.y.edit',
        ]);
    }

    public function test_revoke_all_removes_all_panel_grants(): void
    {
        $user = $this->createUser();

        foreach (['app.a', 'app.b', 'app.c'] as $perm) {
            $this->artisan('az-guard:grant', [
                'user-id'    => $user->getKey(),
                'permission' => $perm,
                'panel'      => 'app',
            ])->assertSuccessful();
        }

        $this->artisan('az-guard:revoke-grant', [
            'user-id'    => $user->getKey(),
            'permission' => '-',   // игнорируется при --all
            'panel'      => 'app',
            '--all'      => true,
            '--force'    => true,
        ])->assertSuccessful();

        $this->assertDatabaseCount('az_guard_direct_grants', 0);
    }

    public function test_revoke_warns_when_grant_not_found(): void
    {
        $user = $this->createUser();

        $this->artisan('az-guard:revoke-grant', [
            'user-id'    => $user->getKey(),
            'permission' => 'app.nonexistent',
            'panel'      => 'app',
            '--force'    => true,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('не найден');
    }
}
