<?php

declare(strict_types=1);

namespace AzGuard\Tests\Registry;

use AzGuard\Registry\Contracts\GrantPriority;
use AzGuard\Registry\Sources\DirectGrantSource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;

final class DirectGrantSourceTest extends TestCase
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
        $app['config']->set('az-guard.table_names.direct_grants', 'az_guard_direct_grants');
    }

    private function setUpDatabase(): void
    {
        DB::statement('CREATE TABLE az_guard_direct_grants (
            id INTEGER PRIMARY KEY,
            model_type TEXT,
            model_id INTEGER,
            permission_key TEXT,
            panel_id TEXT,
            expires_at DATETIME,
            created_at DATETIME,
            updated_at DATETIME
        )');
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

    public function test_returns_empty_set_when_no_grants(): void
    {
        $source = new DirectGrantSource;
        $set = $source->permissionsFor($this->makeUser(1), 'app');

        $this->assertTrue($set->isEmpty());
    }

    public function test_returns_active_grants(): void
    {
        $userClass = get_class($this->makeUser(1));

        DB::table('az_guard_direct_grants')->insert([
            ['model_type' => $userClass, 'model_id' => 1, 'permission_key' => 'app.documents.view',   'panel_id' => 'app', 'expires_at' => null],
            ['model_type' => $userClass, 'model_id' => 1, 'permission_key' => 'app.documents.export', 'panel_id' => 'app', 'expires_at' => null],
        ]);

        $source = new DirectGrantSource;
        $set = $source->permissionsFor($this->makeUser(1), 'app');

        $this->assertTrue($set->contains('app.documents.view'));
        $this->assertTrue($set->contains('app.documents.export'));
    }

    public function test_expired_grants_are_ignored(): void
    {
        $userClass = get_class($this->makeUser(2));

        DB::table('az_guard_direct_grants')->insert([
            // активный
            ['model_type' => $userClass, 'model_id' => 2, 'permission_key' => 'app.x.view',   'panel_id' => 'app', 'expires_at' => null],
            // истёкший
            ['model_type' => $userClass, 'model_id' => 2, 'permission_key' => 'app.x.delete', 'panel_id' => 'app', 'expires_at' => '2000-01-01 00:00:00'],
        ]);

        $source = new DirectGrantSource;
        $set = $source->permissionsFor($this->makeUser(2), 'app');

        $this->assertTrue($set->contains('app.x.view'));
        $this->assertFalse($set->contains('app.x.delete'));
    }

    public function test_future_expiry_grant_is_active(): void
    {
        $userClass = get_class($this->makeUser(3));
        $future = date('Y-m-d H:i:s', strtotime('+1 year'));

        DB::table('az_guard_direct_grants')->insert([
            ['model_type' => $userClass, 'model_id' => 3, 'permission_key' => 'app.y.view', 'panel_id' => 'app', 'expires_at' => $future],
        ]);

        $source = new DirectGrantSource;
        $set = $source->permissionsFor($this->makeUser(3), 'app');

        $this->assertTrue($set->contains('app.y.view'));
    }

    public function test_filters_by_panel_id(): void
    {
        $userClass = get_class($this->makeUser(4));

        DB::table('az_guard_direct_grants')->insert([
            ['model_type' => $userClass, 'model_id' => 4, 'permission_key' => 'app.docs.view',   'panel_id' => 'app',   'expires_at' => null],
            ['model_type' => $userClass, 'model_id' => 4, 'permission_key' => 'admin.docs.view', 'panel_id' => 'admin', 'expires_at' => null],
        ]);

        $source = new DirectGrantSource;
        $set = $source->permissionsFor($this->makeUser(4), 'app');

        $this->assertTrue($set->contains('app.docs.view'));
        $this->assertFalse($set->contains('admin.docs.view'));
    }

    public function test_wildcard_direct_grant_returns_wildcard_set(): void
    {
        $userClass = get_class($this->makeUser(5));

        DB::table('az_guard_direct_grants')->insert([
            ['model_type' => $userClass, 'model_id' => 5, 'permission_key' => '*', 'panel_id' => 'app', 'expires_at' => null],
        ]);

        $source = new DirectGrantSource;
        $set = $source->permissionsFor($this->makeUser(5), 'app');

        $this->assertTrue($set->isWildcard());
    }

    public function test_priority_is_direct_grant(): void
    {
        $this->assertSame(GrantPriority::DirectGrant, (new DirectGrantSource)->priority());
    }
}
