<?php

declare(strict_types=1);

namespace AzGuard\Tests\Registry;

use AzGuard\Registry\Values\PermissionSet;
use PHPUnit\Framework\TestCase;

final class PermissionSetTest extends TestCase
{
    public function test_empty_set_grants_nothing(): void
    {
        $set = PermissionSet::empty();

        $this->assertFalse($set->grants('app.documents.view'));
        $this->assertTrue($set->isEmpty());
        $this->assertFalse($set->isWildcard());
    }

    public function test_wildcard_set_grants_everything(): void
    {
        $set = PermissionSet::wildcard();

        $this->assertTrue($set->grants('app.documents.view'));
        $this->assertTrue($set->grants('admin.users.delete'));
        $this->assertTrue($set->isWildcard());
    }

    public function test_has_exact_key(): void
    {
        $set = PermissionSet::fromKeys(['app.documents.view', 'app.documents.create']);

        $this->assertTrue($set->has('app.documents.view'));
        $this->assertFalse($set->has('app.documents.delete'));
    }

    public function test_wildcard_pattern_matches(): void
    {
        $set = PermissionSet::fromKeys(['app.documents.*']);

        $this->assertTrue($set->grants('app.documents.view'));
        $this->assertTrue($set->grants('app.documents.delete'));
        $this->assertFalse($set->grants('app.users.view'));
    }

    public function test_merge_with_wildcard_returns_wildcard(): void
    {
        $a = PermissionSet::fromKeys(['app.documents.view']);
        $b = PermissionSet::wildcard();

        $this->assertTrue($a->merge($b)->isWildcard());
    }

    public function test_merge_combines_keys(): void
    {
        $a = PermissionSet::fromKeys(['app.documents.view']);
        $b = PermissionSet::fromKeys(['app.users.view']);

        $merged = $a->merge($b);

        $this->assertTrue($merged->has('app.documents.view'));
        $this->assertTrue($merged->has('app.users.view'));
        $this->assertFalse($merged->isWildcard());
    }

    public function test_filter_removes_unknown_keys(): void
    {
        $set = PermissionSet::fromKeys(['app.documents.view', 'orphan.key']);
        $known = ['app.documents.view'];

        $filtered = $set->filter(fn (string $k) => in_array($k, $known, true));

        $this->assertSame(['app.documents.view'], $filtered->toArray());
    }

    public function test_deduplication_on_create(): void
    {
        $set = PermissionSet::fromKeys(['app.x', 'app.x', 'app.y']);

        $this->assertCount(2, $set->toArray());
    }

    public function test_grants_combines_has_and_wildcard_pattern(): void
    {
        $set = PermissionSet::fromKeys(['app.exact', 'app.group.*']);

        $this->assertTrue($set->grants('app.exact'));
        $this->assertTrue($set->grants('app.group.view'));
        $this->assertFalse($set->grants('app.other'));
    }

    public function test_keys_returns_list(): void
    {
        $set = PermissionSet::fromKeys(['app.a', 'app.b']);

        $this->assertSame(['app.a', 'app.b'], $set->keys());
        $this->assertSame($set->keys(), $set->toArray());
    }

    public function test_count(): void
    {
        $this->assertSame(0, PermissionSet::empty()->count());
        $this->assertSame(1, PermissionSet::wildcard()->count());
        $this->assertSame(3, PermissionSet::fromKeys(['a', 'b', 'c'])->count());
    }

    public function test_wildcard_has_returns_true_for_any_key(): void
    {
        $set = PermissionSet::wildcard();

        $this->assertTrue($set->has('any.random.key'));
    }
}
