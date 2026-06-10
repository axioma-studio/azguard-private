<?php

declare(strict_types=1);

namespace AzGuard\Tests\Concerns;

use AzGuard\AzGuardServiceProvider;
use AzGuard\Concerns\HasAzGuard;
use AzGuard\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class HasAzGuardStubUser extends Model
{
    use HasAzGuard;

    protected $table = 'stub_az_users';

    protected $fillable = ['id'];

    public $timestamps = false;
}

final class HasAzGuardTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['db']->statement(
            'CREATE TABLE IF NOT EXISTS stub_az_users (id INTEGER PRIMARY KEY AUTOINCREMENT)',
        );
    }

    private function createUser(): HasAzGuardStubUser
    {
        return HasAzGuardStubUser::create([]);
    }

    private function createRole(string $name): Role
    {
        return Role::create(['name' => $name, 'guard_name' => 'web']);
    }

    // ------------------------------------------------------------------

    public function test_has_role_returns_true_after_assign(): void
    {
        $user = $this->createUser();
        $this->createRole('editor');

        $user->assignRole('editor');

        $this->assertTrue($user->hasRole('editor'));
    }

    public function test_has_role_returns_false_after_remove(): void
    {
        $user = $this->createUser();
        $this->createRole('editor');

        $user->assignRole('editor');
        $user->removeRole('editor');

        $this->assertFalse($user->hasRole('editor'));
    }

    public function test_sync_roles_replaces_all_roles(): void
    {
        $user = $this->createUser();
        $this->createRole('editor');
        $this->createRole('viewer');

        $user->assignRole('editor');
        $user->syncRoles(['viewer']);

        $this->assertFalse($user->fresh()->hasRole('editor'));
        $this->assertTrue($user->fresh()->hasRole('viewer'));
    }

    public function test_get_role_names_returns_collection(): void
    {
        $user = $this->createUser();
        $this->createRole('admin');
        $this->createRole('viewer');

        $user->assignRole('admin');
        $user->assignRole('viewer');

        $names = $user->getRoleNames();

        $this->assertCount(2, $names);
        $this->assertTrue($names->contains('admin'));
        $this->assertTrue($names->contains('viewer'));
    }

    public function test_check_permission_returns_false_on_error(): void
    {
        $user = $this->createUser();

        // checkPermission() — silent version, never throws
        $result = $user->checkPermission('app.something.view');

        $this->assertFalse($result);
    }

    public function test_permissions_returns_empty_collection_with_no_roles(): void
    {
        $user = $this->createUser();

        $permissions = $user->permissions();

        $this->assertTrue($permissions->isEmpty());
    }

    public function test_flush_permissions_does_not_throw(): void
    {
        $user = $this->createUser();

        // should not throw even with empty cache
        $user->flushPermissions();

        $this->assertTrue(true);
    }

    public function test_assign_role_is_idempotent(): void
    {
        $user = $this->createUser();
        $this->createRole('editor');

        $user->assignRole('editor');
        $user->assignRole('editor'); // повторно

        $this->assertCount(1, $user->fresh()->roles);
    }

    public function test_assign_nonexistent_role_does_nothing(): void
    {
        $user = $this->createUser();

        $user->assignRole('ghost-role'); // не существует

        $this->assertCount(0, $user->fresh()->roles);
    }

    public function test_scopes_relation_exists(): void
    {
        $user = $this->createUser();

        // Relation должна быть доступна без ошибок
        $scopes = $user->scopes()->get();

        $this->assertTrue($scopes->isEmpty());
    }
}
