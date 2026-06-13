<?php

declare(strict_types=1);

namespace AzGuard\Context\Tests;

use AzGuard\AzGuardServiceProvider;
use AzGuard\Context\AuthorizationContext;
use AzGuard\Context\AuthorizationContextManager;
use AzGuard\Context\AzGuardContextServiceProvider;
use AzGuard\Context\ContextualRoleGrantSource;
use AzGuard\Context\Strategies\GlobalPlusContextStrategy;
use AzGuard\Registry\Contracts\GrantPriority;
use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

final class ContextualRoleGrantSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            AzGuardServiceProvider::class,
            AzGuardContextServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('az-guard.table_names.context_roles', 'az_guard_context_roles');
    }

    private function makeSource(?AuthorizationContext $context = null): ContextualRoleGrantSource
    {
        $manager = new AuthorizationContextManager;

        if ($context !== null) {
            $manager->set($context);
        }

        return new ContextualRoleGrantSource($manager, new GlobalPlusContextStrategy);
    }

    private function user(): GenericUser
    {
        return new GenericUser(['id' => 1]);
    }

    private function insertPermission(
        GenericUser $user,
        string $contextType,
        string|int $contextId,
        string $panelId,
        string $permissionKey,
    ): void {
        $this->app['db']->table('az_guard_context_roles')->insert([
            'model_type' => GenericUser::class,
            'model_id' => $user->getAuthIdentifier(),
            'context_type' => $contextType,
            'context_id' => (string) $contextId,
            'panel_id' => $panelId,
            'permission_key' => $permissionKey,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // -----------------------------------------------------------------------

    public function test_priority_is_contextual_role(): void
    {
        $source = $this->makeSource();
        $this->assertSame(GrantPriority::ContextualRole, $source->priority());
    }

    public function test_no_context_returns_empty_set(): void
    {
        $source = $this->makeSource(); // context не установлен
        $set = $source->permissionsFor($this->user(), 'app');

        $this->assertFalse($set->isWildcard());
        $this->assertCount(0, $set->keys());
    }

    public function test_with_context_no_rows_returns_empty(): void
    {
        $ctx = new AuthorizationContext('app', 'workspace', 42);
        $source = $this->makeSource($ctx);

        $set = $source->permissionsFor($this->user(), 'app');

        $this->assertCount(0, $set->keys());
    }

    public function test_with_context_returns_matching_keys(): void
    {
        $user = $this->user();
        $ctx = new AuthorizationContext('app', 'workspace', 42);

        $this->insertPermission($user, 'workspace', 42, 'app', 'app.posts.view');
        $this->insertPermission($user, 'workspace', 42, 'app', 'app.posts.edit');
        // другой контекст — не должен попасть
        $this->insertPermission($user, 'workspace', 99, 'app', 'app.posts.delete');

        $source = $this->makeSource($ctx);
        $set = $source->permissionsFor($user, 'app');

        $this->assertTrue($set->grants('app.posts.view'));
        $this->assertTrue($set->grants('app.posts.edit'));
        $this->assertFalse($set->grants('app.posts.delete'));
    }

    public function test_wildcard_row_returns_wildcard_set(): void
    {
        $user = $this->user();
        $ctx = new AuthorizationContext('app', 'workspace', 42);

        $this->insertPermission($user, 'workspace', 42, 'app', '*');

        $source = $this->makeSource($ctx);
        $set = $source->permissionsFor($user, 'app');

        $this->assertTrue($set->isWildcard());
    }

    public function test_rows_for_other_panel_not_returned(): void
    {
        $user = $this->user();
        $ctx = new AuthorizationContext('app', 'workspace', 42);

        $this->insertPermission($user, 'workspace', 42, 'admin', 'admin.users.view');

        $source = $this->makeSource($ctx);
        $set = $source->permissionsFor($user, 'app');

        $this->assertFalse($set->grants('admin.users.view'));
        $this->assertCount(0, $set->keys());
    }
}
