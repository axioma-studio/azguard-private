<?php

declare(strict_types=1);

namespace AzGuard\Tests\Registry;

use AzGuard\Registry\Contracts\GrantPriority;
use AzGuard\Registry\Sources\DatabaseRoleGrantSource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;

final class DatabaseRoleGrantSourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
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

    private function setUpDatabase(): void
    {
        DB::statement('CREATE TABLE az_guard_roles (id INTEGER PRIMARY KEY, name TEXT, class_name TEXT, level INTEGER DEFAULT 0)');
        DB::statement('CREATE TABLE az_guard_model_has_roles (role_id INTEGER, model_type TEXT, model_id INTEGER)');
        DB::statement('CREATE TABLE az_guard_role_permissions (id INTEGER PRIMARY KEY, role_id INTEGER, permission_key TEXT, panel_id TEXT)');
    }

    private function makeUser(int $id): Authenticatable
    {
        return new class($id) implements Authenticatable
        {
            public function __construct(private int $id) {}

            public function getAuthIdentifierName(): string
            {
                return 'id';
            }

            public function getAuthIdentifier()
            {
                return $this->id;
            }

            public function getAuthPasswordName(): string
            {
                return 'password';
            }

            public function getAuthPassword(): string
            {
                return '';
            }

            public function getRememberToken(): string
            {
                return '';
            }

            public function setRememberToken($value): void {}

            public function getRememberTokenName(): string
            {
                return '';
            }
        };
    }

    public function test_returns_empty_set_when_no_roles(): void
    {
        $source = new DatabaseRoleGrantSource;
        $user = $this->makeUser(1);

        $set = $source->permissionsFor($user, 'app');

        $this->assertTrue($set->isEmpty());
    }

    public function test_returns_permissions_from_db_role(): void
    {
        DB::table('az_guard_roles')->insert(['id' => 1, 'name' => 'editor', 'level' => 0]);
        DB::table('az_guard_model_has_roles')->insert([
            'role_id' => 1,
            'model_type' => get_class($this->makeUser(1)),
            'model_id' => 1,
        ]);
        DB::table('az_guard_role_permissions')->insert([
            ['role_id' => 1, 'permission_key' => 'app.documents.view',   'panel_id' => 'app'],
            ['role_id' => 1, 'permission_key' => 'app.documents.create', 'panel_id' => 'app'],
        ]);

        $source = new DatabaseRoleGrantSource;
        $set = $source->permissionsFor($this->makeUser(1), 'app');

        $this->assertTrue($set->contains('app.documents.view'));
        $this->assertTrue($set->contains('app.documents.create'));
        $this->assertFalse($set->contains('app.documents.delete'));
    }

    public function test_filters_by_panel_id(): void
    {
        DB::table('az_guard_roles')->insert(['id' => 2, 'name' => 'admin', 'level' => 10]);
        DB::table('az_guard_model_has_roles')->insert([
            'role_id' => 2,
            'model_type' => get_class($this->makeUser(2)),
            'model_id' => 2,
        ]);
        DB::table('az_guard_role_permissions')->insert([
            ['role_id' => 2, 'permission_key' => 'app.users.view',   'panel_id' => 'app'],
            ['role_id' => 2, 'permission_key' => 'admin.users.view', 'panel_id' => 'admin'],
        ]);

        $source = new DatabaseRoleGrantSource;
        $set = $source->permissionsFor($this->makeUser(2), 'app');

        $this->assertTrue($set->contains('app.users.view'));
        $this->assertFalse($set->contains('admin.users.view'));
    }

    public function test_wildcard_key_returns_wildcard_set(): void
    {
        DB::table('az_guard_roles')->insert(['id' => 3, 'name' => 'superadmin', 'level' => 99]);
        DB::table('az_guard_model_has_roles')->insert([
            'role_id' => 3,
            'model_type' => get_class($this->makeUser(3)),
            'model_id' => 3,
        ]);
        DB::table('az_guard_role_permissions')->insert([
            ['role_id' => 3, 'permission_key' => '*', 'panel_id' => 'app'],
        ]);

        $source = new DatabaseRoleGrantSource;
        $set = $source->permissionsFor($this->makeUser(3), 'app');

        $this->assertTrue($set->isWildcard());
    }

    public function test_priority_is_database_role(): void
    {
        $this->assertSame(GrantPriority::DatabaseRole, (new DatabaseRoleGrantSource)->priority());
    }
}
