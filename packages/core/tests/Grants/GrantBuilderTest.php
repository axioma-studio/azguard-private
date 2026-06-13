<?php

declare(strict_types=1);

namespace AzGuard\Tests\Grants;

use AzGuard\AzGuardServiceProvider;
use AzGuard\Events\GrantGiven;
use AzGuard\Events\GrantRevoked;
use AzGuard\Grants\GrantBuilder;
use AzGuard\Models\DirectGrant;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;

final class GrantBuilderTest extends TestCase
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

    private function makeUser(int $id = 1): Authenticatable
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

    public function test_give_creates_grant_and_fires_event(): void
    {
        Event::fake([GrantGiven::class]);

        $user = $this->makeUser(1);

        $grant = (new GrantBuilder($user))
            ->on('app')
            ->grant('app.documents.view');

        $this->assertInstanceOf(DirectGrant::class, $grant);
        $this->assertDatabaseHas('az_guard_direct_grants', [
            'model_id' => 1,
            'permission_key' => 'app.documents.view',
            'panel_id' => 'app',
            'expires_at' => null,
        ]);

        Event::assertDispatched(GrantGiven::class, fn ($e) => $e->permissionKey === 'app.documents.view' &&
            $e->panelId === 'app' &&
            $e->expiresAt === null,
        );
    }

    public function test_give_with_ttl_sets_expires_at(): void
    {
        Event::fake([GrantGiven::class]);

        $user = $this->makeUser(2);
        $before = now()->addSeconds(3590);
        $after = now()->addSeconds(3610);

        $grant = (new GrantBuilder($user))
            ->on('app')
            ->ttl(3600)
            ->grant('app.x.edit');

        $this->assertTrue($grant->expires_at->between($before, $after));

        Event::assertDispatched(GrantGiven::class, fn ($e) => $e->expiresAt !== null);
    }

    public function test_give_is_idempotent_upsert(): void
    {
        $user = $this->makeUser(3);

        (new GrantBuilder($user))->on('app')->grant('app.y.view');
        (new GrantBuilder($user))->on('app')->ttl(7200)->grant('app.y.view');

        $this->assertDatabaseCount('az_guard_direct_grants', 1);
        $grant = DirectGrant::first();
        $this->assertNotNull($grant->expires_at);
    }

    public function test_revoke_deletes_grant_and_fires_event(): void
    {
        Event::fake([GrantRevoked::class]);

        $user = $this->makeUser(4);
        (new GrantBuilder($user))->on('app')->grant('app.z.delete');

        $deleted = (new GrantBuilder($user))->on('app')->revoke('app.z.delete');

        $this->assertSame(1, $deleted);
        $this->assertDatabaseMissing('az_guard_direct_grants', [
            'model_id' => 4,
            'permission_key' => 'app.z.delete',
        ]);
        Event::assertDispatched(GrantRevoked::class, fn ($e) => $e->permissionKey === 'app.z.delete',
        );
    }

    public function test_revoke_returns_zero_when_not_found(): void
    {
        Event::fake([GrantRevoked::class]);

        $deleted = (new GrantBuilder($this->makeUser(5)))
            ->on('app')
            ->revoke('app.nonexistent');

        $this->assertSame(0, $deleted);
        Event::assertNotDispatched(GrantRevoked::class);
    }

    public function test_revoke_all_deletes_panel_grants_only(): void
    {
        Event::fake([GrantRevoked::class]);

        $user = $this->makeUser(6);
        (new GrantBuilder($user))->on('app')->grant('app.a.view');
        (new GrantBuilder($user))->on('app')->grant('app.b.view');
        (new GrantBuilder($user))->on('admin')->grant('admin.c.view');

        $deleted = (new GrantBuilder($user))->on('app')->revokeAll();

        $this->assertSame(2, $deleted);
        $this->assertDatabaseCount('az_guard_direct_grants', 1); // admin grant stays
        Event::assertDispatchedTimes(GrantRevoked::class, 2);
    }

    public function test_list_returns_active_grants_only(): void
    {
        $user = $this->makeUser(7);
        (new GrantBuilder($user))->on('app')->grant('app.active.view');

        // Добавляем истекший grant напрямую
        DirectGrant::create([
            'model_type' => get_class($user),
            'model_id' => 7,
            'permission_key' => 'app.expired.view',
            'panel_id' => 'app',
            'expires_at' => now()->subHour(),
        ]);

        $list = (new GrantBuilder($user))->on('app')->grants();

        $this->assertCount(1, $list);
        $this->assertSame('app.active.view', $list->first()->permission_key);
    }

    public function test_on_returns_immutable_clone(): void
    {
        $user = $this->makeUser(8);
        $builder = new GrantBuilder($user);
        $clone = $builder->on('admin');

        // Оригинал и клон — разные объекты
        $this->assertNotSame($builder, $clone);
    }
}
