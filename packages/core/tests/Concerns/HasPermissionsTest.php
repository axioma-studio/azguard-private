<?php

declare(strict_types=1);

namespace AzGuard\Tests\Concerns;

use AzGuard\Concerns\HasPermissions;
use AzGuard\Registry\Values\PermissionSet;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Stub that uses HasPermissions with a controlled PermissionSet.
 * Overrides permissionSet() so no service container is needed.
 */
class HasPermissionsStubUser
{
    use HasPermissions;

    private PermissionSet $stubbedSet;

    public function withSet(PermissionSet $set): static
    {
        $this->stubbedSet = $set;

        return $this;
    }

    public function permissionSet(string $panelId = 'app'): PermissionSet
    {
        return $this->stubbedSet ?? PermissionSet::empty();
    }

    public function flushPermissions(?string $panelId = null): void
    {
        // no-op in unit context
    }
}

final class HasPermissionsTest extends TestCase
{
    private function makeUser(PermissionSet $set): HasPermissionsStubUser
    {
        return (new HasPermissionsStubUser)->withSet($set);
    }

    public function test_has_permission_returns_true_when_granted(): void
    {
        $user = $this->makeUser(PermissionSet::fromKeys(['app.posts.view']));

        $this->assertTrue($user->hasPermission('app.posts.view'));
    }

    public function test_has_permission_returns_false_when_not_granted(): void
    {
        $user = $this->makeUser(PermissionSet::fromKeys(['app.posts.view']));

        $this->assertFalse($user->hasPermission('app.posts.delete'));
    }

    public function test_has_permission_returns_true_for_wildcard_set(): void
    {
        $user = $this->makeUser(PermissionSet::wildcard());

        $this->assertTrue($user->hasPermission('app.anything.at.all'));
    }

    public function test_has_permission_returns_false_for_empty_set(): void
    {
        $user = $this->makeUser(PermissionSet::empty());

        $this->assertFalse($user->hasPermission('app.posts.view'));
    }

    public function test_check_permission_returns_false_when_has_permission_throws(): void
    {
        $user = new class extends HasPermissionsStubUser
        {
            public function hasPermission(string $permission, string $panelId = 'app', ?object $context = null): bool
            {
                throw new RuntimeException('resolver exploded');
            }
        };

        $this->assertFalse($user->checkPermission('app.posts.view'));
    }

    public function test_check_permission_returns_true_when_has_permission_does(): void
    {
        $user = $this->makeUser(PermissionSet::fromKeys(['app.posts.view']));

        $this->assertTrue($user->checkPermission('app.posts.view'));
    }

    public function test_permissions_returns_collection_of_keys(): void
    {
        $user = $this->makeUser(PermissionSet::fromKeys(['app.posts.view', 'app.posts.edit']));

        $keys = $user->permissions();

        $this->assertCount(2, $keys);
        $this->assertTrue($keys->contains('app.posts.view'));
        $this->assertTrue($keys->contains('app.posts.edit'));
    }

    public function test_permissions_returns_empty_collection_for_empty_set(): void
    {
        $user = $this->makeUser(PermissionSet::empty());

        $this->assertTrue($user->permissions()->isEmpty());
    }

    public function test_wildcard_permission_check_on_any_key(): void
    {
        $user = $this->makeUser(PermissionSet::wildcard());

        $this->assertTrue($user->hasPermission('app.some.totally.unknown.key'));
        $this->assertTrue($user->checkPermission('anything'));
    }
}
