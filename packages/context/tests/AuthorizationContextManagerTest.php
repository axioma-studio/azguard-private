<?php

declare(strict_types=1);

namespace AzGuard\Context\Tests;

use AzGuard\Context\AuthorizationContext;
use AzGuard\Context\AuthorizationContextManager;
use PHPUnit\Framework\TestCase;

final class AuthorizationContextManagerTest extends TestCase
{
    private AuthorizationContextManager $manager;

    protected function setUp(): void
    {
        $this->manager = new AuthorizationContextManager;
    }

    public function test_current_returns_null_when_not_set(): void
    {
        $this->assertNull($this->manager->current('app'));
    }

    public function test_has_returns_false_when_not_set(): void
    {
        $this->assertFalse($this->manager->has('app'));
    }

    public function test_set_and_current(): void
    {
        $ctx = new AuthorizationContext('app', 'workspace', 42);
        $this->manager->set($ctx);

        $this->assertSame($ctx, $this->manager->current('app'));
    }

    public function test_has_returns_true_after_set(): void
    {
        $ctx = new AuthorizationContext('app', 'workspace', 42);
        $this->manager->set($ctx);

        $this->assertTrue($this->manager->has('app'));
    }

    public function test_set_overwrites_previous(): void
    {
        $ctx1 = new AuthorizationContext('app', 'workspace', 1);
        $ctx2 = new AuthorizationContext('app', 'workspace', 2);

        $this->manager->set($ctx1);
        $this->manager->set($ctx2);

        $this->assertSame($ctx2, $this->manager->current('app'));
    }

    public function test_panels_are_isolated(): void
    {
        $ctxApp = new AuthorizationContext('app', 'workspace', 1);
        $ctxAdmin = new AuthorizationContext('admin', 'workspace', 2);

        $this->manager->set($ctxApp);
        $this->manager->set($ctxAdmin);

        $this->assertSame($ctxApp, $this->manager->current('app'));
        $this->assertSame($ctxAdmin, $this->manager->current('admin'));
    }

    public function test_clear_removes_context_for_panel(): void
    {
        $ctx = new AuthorizationContext('app', 'workspace', 42);
        $this->manager->set($ctx);
        $this->manager->clear('app');

        $this->assertNull($this->manager->current('app'));
        $this->assertFalse($this->manager->has('app'));
    }

    public function test_clear_does_not_affect_other_panels(): void
    {
        $ctxApp = new AuthorizationContext('app', 'workspace', 1);
        $ctxAdmin = new AuthorizationContext('admin', 'workspace', 2);

        $this->manager->set($ctxApp);
        $this->manager->set($ctxAdmin);
        $this->manager->clear('app');

        $this->assertNull($this->manager->current('app'));
        $this->assertSame($ctxAdmin, $this->manager->current('admin'));
    }

    public function test_clear_all_removes_all_contexts(): void
    {
        $this->manager->set(new AuthorizationContext('app', 'workspace', 1));
        $this->manager->set(new AuthorizationContext('admin', 'workspace', 2));
        $this->manager->clearAll();

        $this->assertNull($this->manager->current('app'));
        $this->assertNull($this->manager->current('admin'));
    }
}
