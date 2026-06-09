<?php

declare(strict_types=1);

namespace AzGuard\Context\Tests;

use AzGuard\Context\AuthorizationContext;
use PHPUnit\Framework\TestCase;

final class AuthorizationContextTest extends TestCase
{
    public function test_constructor_sets_properties(): void
    {
        $ctx = new AuthorizationContext('app', 'workspace', 42);

        $this->assertSame('app', $ctx->panelId);
        $this->assertSame('workspace', $ctx->contextType);
        $this->assertSame(42, $ctx->contextId);
    }

    public function test_with_panel_returns_new_instance(): void
    {
        $ctx = new AuthorizationContext('app', 'workspace', 42);
        $ctx2 = $ctx->withPanel('admin');

        $this->assertNotSame($ctx, $ctx2);
        $this->assertSame('admin', $ctx2->panelId);
        $this->assertSame('workspace', $ctx2->contextType);
        $this->assertSame(42, $ctx2->contextId);
    }

    public function test_with_context_returns_new_instance(): void
    {
        $ctx = new AuthorizationContext('app', 'workspace', 42);
        $ctx2 = $ctx->withContext('project', 'abc');

        $this->assertNotSame($ctx, $ctx2);
        $this->assertSame('app', $ctx2->panelId);
        $this->assertSame('project', $ctx2->contextType);
        $this->assertSame('abc', $ctx2->contextId);
    }

    public function test_original_unchanged_after_with_panel(): void
    {
        $ctx = new AuthorizationContext('app', 'workspace', 42);
        $ctx->withPanel('admin');

        $this->assertSame('app', $ctx->panelId);
    }

    public function test_cache_key_format(): void
    {
        $ctx = new AuthorizationContext('app', 'workspace', 42);

        $this->assertSame('app:workspace:42', $ctx->cacheKey());
    }

    public function test_cache_key_with_string_id(): void
    {
        $ctx = new AuthorizationContext('app', 'project', 'uuid-123');

        $this->assertSame('app:project:uuid-123', $ctx->cacheKey());
    }

    public function test_equals_same_values(): void
    {
        $a = new AuthorizationContext('app', 'workspace', 42);
        $b = new AuthorizationContext('app', 'workspace', 42);

        $this->assertTrue($a->equals($b));
    }

    public function test_equals_different_panel(): void
    {
        $a = new AuthorizationContext('app', 'workspace', 42);
        $b = new AuthorizationContext('admin', 'workspace', 42);

        $this->assertFalse($a->equals($b));
    }

    public function test_equals_different_context_type(): void
    {
        $a = new AuthorizationContext('app', 'workspace', 42);
        $b = new AuthorizationContext('app', 'project', 42);

        $this->assertFalse($a->equals($b));
    }

    public function test_equals_different_context_id(): void
    {
        $a = new AuthorizationContext('app', 'workspace', 42);
        $b = new AuthorizationContext('app', 'workspace', 99);

        $this->assertFalse($a->equals($b));
    }
}
