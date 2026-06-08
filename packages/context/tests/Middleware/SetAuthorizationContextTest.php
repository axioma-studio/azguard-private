<?php

declare(strict_types=1);

namespace AzGuard\Context\Tests\Middleware;

use AzGuard\Context\AuthorizationContext;
use AzGuard\Context\AuthorizationContextManager;
use AzGuard\Context\Contracts\ResolvesContext;
use AzGuard\Context\Middleware\SetAuthorizationContext;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;

final class SetAuthorizationContextTest extends TestCase
{
    public function test_resolver_is_called_and_context_set(): void
    {
        $manager = new AuthorizationContextManager();
        $ctx     = new AuthorizationContext('app', 'workspace', 42);

        $resolver = new class ($ctx) implements ResolvesContext {
            public function __construct(private AuthorizationContext $ctx) {}
            public function resolve(Request $r): ?AuthorizationContext { return $this->ctx; }
            public function panel(): string { return 'app'; }
        };

        $middleware = new SetAuthorizationContext($manager, [$resolver]);
        $request    = Request::create('/');

        $middleware->handle($request, function () { return new Response(); });

        $this->assertTrue($manager->has('app'));
        $this->assertTrue($ctx->equals($manager->current('app')));
    }

    public function test_null_from_resolver_is_skipped(): void
    {
        $manager = new AuthorizationContextManager();

        $resolver = new class implements ResolvesContext {
            public function resolve(Request $r): ?AuthorizationContext { return null; }
            public function panel(): string { return 'app'; }
        };

        $middleware = new SetAuthorizationContext($manager, [$resolver]);
        $request    = Request::create('/');

        $middleware->handle($request, function () { return new Response(); });

        $this->assertFalse($manager->has('app'));
    }

    public function test_multiple_resolvers_set_multiple_panels(): void
    {
        $manager  = new AuthorizationContextManager();
        $ctxApp   = new AuthorizationContext('app', 'workspace', 1);
        $ctxAdmin = new AuthorizationContext('admin', 'workspace', 2);

        $r1 = new class ($ctxApp) implements ResolvesContext {
            public function __construct(private AuthorizationContext $ctx) {}
            public function resolve(Request $r): ?AuthorizationContext { return $this->ctx; }
            public function panel(): string { return 'app'; }
        };
        $r2 = new class ($ctxAdmin) implements ResolvesContext {
            public function __construct(private AuthorizationContext $ctx) {}
            public function resolve(Request $r): ?AuthorizationContext { return $this->ctx; }
            public function panel(): string { return 'admin'; }
        };

        $middleware = new SetAuthorizationContext($manager, [$r1, $r2]);
        $middleware->handle(Request::create('/'), fn() => new Response());

        $this->assertTrue($manager->has('app'));
        $this->assertTrue($manager->has('admin'));
    }

    public function test_next_is_always_called(): void
    {
        $manager    = new AuthorizationContextManager();
        $middleware = new SetAuthorizationContext($manager, []);
        $called     = false;

        $middleware->handle(Request::create('/'), function () use (&$called) {
            $called = true;
            return new Response();
        });

        $this->assertTrue($called);
    }
}
