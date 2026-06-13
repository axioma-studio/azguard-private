<?php

declare(strict_types=1);

namespace AzGuard\Context\Tests\Strategies;

use AzGuard\Context\Strategies\ContextOnlyStrategy;
use AzGuard\Context\Strategies\DenyWithoutContextStrategy;
use AzGuard\Context\Strategies\GlobalPlusContextStrategy;
use AzGuard\Registry\Values\PermissionSet;
use PHPUnit\Framework\TestCase;

final class StrategyTest extends TestCase
{
    // -----------------------------------------------------------------------
    // GlobalPlusContextStrategy
    // -----------------------------------------------------------------------

    public function test_global_plus_context_no_context_returns_global(): void
    {
        $strategy = new GlobalPlusContextStrategy;
        $global = PermissionSet::fromKeys(['app.posts.view']);

        $result = $strategy->merge($global, null);

        $this->assertTrue($result->grants('app.posts.view'));
        $this->assertFalse($result->grants('app.posts.edit'));
    }

    public function test_global_plus_context_merges_both(): void
    {
        $strategy = new GlobalPlusContextStrategy;
        $global = PermissionSet::fromKeys(['app.posts.view']);
        $context = PermissionSet::fromKeys(['app.posts.edit']);

        $result = $strategy->merge($global, $context);

        $this->assertTrue($result->grants('app.posts.view'));
        $this->assertTrue($result->grants('app.posts.edit'));
    }

    public function test_global_plus_context_wildcard_context_propagates(): void
    {
        $strategy = new GlobalPlusContextStrategy;
        $global = PermissionSet::fromKeys(['app.posts.view']);
        $context = PermissionSet::wildcard();

        $result = $strategy->merge($global, $context);

        $this->assertTrue($result->isWildcard());
    }

    // -----------------------------------------------------------------------
    // ContextOnlyStrategy
    // -----------------------------------------------------------------------

    public function test_context_only_no_context_returns_empty(): void
    {
        $strategy = new ContextOnlyStrategy;
        $global = PermissionSet::fromKeys(['app.posts.view']);

        $result = $strategy->merge($global, null);

        $this->assertFalse($result->grants('app.posts.view'));
        $this->assertCount(0, $result->keys());
    }

    public function test_context_only_ignores_global(): void
    {
        $strategy = new ContextOnlyStrategy;
        $global = PermissionSet::fromKeys(['app.posts.view']);
        $context = PermissionSet::fromKeys(['app.posts.edit']);

        $result = $strategy->merge($global, $context);

        $this->assertFalse($result->grants('app.posts.view'));
        $this->assertTrue($result->grants('app.posts.edit'));
    }

    // -----------------------------------------------------------------------
    // DenyWithoutContextStrategy
    // -----------------------------------------------------------------------

    public function test_deny_without_context_no_context_returns_empty(): void
    {
        $strategy = new DenyWithoutContextStrategy;
        $global = PermissionSet::fromKeys(['app.posts.view']);

        $result = $strategy->merge($global, null);

        $this->assertFalse($result->grants('app.posts.view'));
    }

    public function test_deny_without_context_with_context_merges(): void
    {
        $strategy = new DenyWithoutContextStrategy;
        $global = PermissionSet::fromKeys(['app.posts.view']);
        $context = PermissionSet::fromKeys(['app.posts.edit']);

        $result = $strategy->merge($global, $context);

        $this->assertTrue($result->grants('app.posts.view'));
        $this->assertTrue($result->grants('app.posts.edit'));
    }

    public function test_deny_without_context_with_empty_context_returns_global(): void
    {
        $strategy = new DenyWithoutContextStrategy;
        $global = PermissionSet::fromKeys(['app.posts.view']);
        $context = PermissionSet::empty();

        $result = $strategy->merge($global, $context);

        // context передан (не null) => merge = global union empty = global
        $this->assertTrue($result->grants('app.posts.view'));
    }
}
