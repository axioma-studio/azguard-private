<?php

declare(strict_types=1);

namespace AzGuard\Tests\Concerns;

use AzGuard\AzGuardServiceProvider;
use AzGuard\Concerns\HasAzGuard;
use AzGuard\Concerns\HasDirectGrants;
use AzGuard\Grants\GrantBuilder;
use AzGuard\Models\DirectGrant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class StubUserModel extends Model
{
    use HasAzGuard;
    use HasDirectGrants;

    protected $table = 'stub_users';

    protected $fillable = ['id'];

    public $timestamps = false;
}

final class HasDirectGrantsTest extends TestCase
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
            'CREATE TABLE IF NOT EXISTS stub_users (id INTEGER PRIMARY KEY AUTOINCREMENT)'
        );
    }

    private function createStubUser(): StubUserModel
    {
        return StubUserModel::create([]);
    }

    // ------------------------------------------------------------------

    public function test_has_direct_grant_returns_true_when_grant_exists(): void
    {
        $user = $this->createStubUser();
        (new GrantBuilder($user))->on('app')->give('app.x.view');

        $this->assertTrue($user->hasDirectGrant('app.x.view', 'app'));
    }

    public function test_has_direct_grant_returns_false_when_grant_missing(): void
    {
        $user = $this->createStubUser();

        $this->assertFalse($user->hasDirectGrant('app.x.view', 'app'));
    }

    public function test_has_direct_grant_returns_false_for_expired_grant(): void
    {
        $user = $this->createStubUser();

        DirectGrant::create([
            'model_type' => StubUserModel::class,
            'model_id' => $user->getKey(),
            'permission_key' => 'app.x.view',
            'panel_id' => 'app',
            'expires_at' => now()->subMinute(),
        ]);

        $this->assertFalse($user->hasDirectGrant('app.x.view', 'app'));
    }

    public function test_has_direct_grant_is_cached_in_memory(): void
    {
        $user = $this->createStubUser();
        (new GrantBuilder($user))->on('app')->give('app.y.view');

        $user->hasDirectGrant('app.y.view', 'app');
        DirectGrant::query()->delete();

        $this->assertTrue($user->hasDirectGrant('app.y.view', 'app'));
    }

    public function test_clear_direct_grants_cache_invalidates_memory_cache(): void
    {
        $user = $this->createStubUser();
        (new GrantBuilder($user))->on('app')->give('app.z.view');

        $user->hasDirectGrant('app.z.view', 'app');
        DirectGrant::query()->delete();
        $user->clearDirectGrantsCache();

        $this->assertFalse($user->hasDirectGrant('app.z.view', 'app'));
    }

    public function test_direct_grants_returns_active_collection(): void
    {
        $user = $this->createStubUser();
        (new GrantBuilder($user))->on('app')->give('app.a.view');
        (new GrantBuilder($user))->on('app')->give('app.b.edit');

        DirectGrant::create([
            'model_type' => StubUserModel::class,
            'model_id' => $user->getKey(),
            'permission_key' => 'app.c.delete',
            'panel_id' => 'app',
            'expires_at' => now()->subSecond(),
        ]);

        $grants = $user->directGrants('app');

        $this->assertCount(2, $grants);
        $this->assertTrue($grants->pluck('permission_key')->contains('app.a.view'));
    }

    public function test_direct_grants_without_panel_returns_all_active(): void
    {
        $user = $this->createStubUser();
        (new GrantBuilder($user))->on('app')->give('app.x.view');
        (new GrantBuilder($user))->on('admin')->give('admin.x.view');

        $this->assertCount(2, $user->directGrants());
    }

    public function test_has_permission_checks_direct_grant_as_fallback(): void
    {
        $user = $this->createStubUser();
        (new GrantBuilder($user))->on('app')->give('app.reports.view');

        $this->assertTrue($user->hasPermission('app.reports.view'));
    }

    public function test_has_permission_returns_false_without_role_and_grant(): void
    {
        $user = $this->createStubUser();

        $this->assertFalse($user->hasPermission('app.reports.view'));
    }

    public function test_flush_permissions_clears_both_caches(): void
    {
        $user = $this->createStubUser();
        (new GrantBuilder($user))->on('app')->give('app.test.view');

        $user->hasDirectGrant('app.test.view', 'app');
        DirectGrant::query()->delete();
        $user->flushPermissions();

        $this->assertFalse($user->hasDirectGrant('app.test.view', 'app'));
    }
}
