<?php

declare(strict_types=1);

namespace AzGuard\Tests\Http\Middleware;

use AzGuard\AzGuardServiceProvider;
use AzGuard\Grants\GrantBuilder;
use AzGuard\Http\Middleware\CheckDirectGrant;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Orchestra\Testbench\TestCase;

final class CheckDirectGrantMiddlewareTest extends TestCase
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
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('az-guard.table_names', [
            'roles'            => 'az_guard_roles',
            'model_has_roles'  => 'az_guard_model_has_roles',
            'model_has_scopes' => 'az_guard_model_has_scopes',
            'role_permissions' => 'az_guard_role_permissions',
            'direct_grants'    => 'az_guard_direct_grants',
        ]);
    }

    private function makeUser(int $id = 1): Authenticatable
    {
        return new class($id) implements Authenticatable {
            public function __construct(private int $id) {}
            public function getAuthIdentifierName(): string { return 'id'; }
            public function getAuthIdentifier() { return $this->id; }
            public function getAuthPasswordName(): string { return 'password'; }
            public function getAuthPassword(): string { return ''; }
            public function getRememberToken(): string { return ''; }
            public function setRememberToken($value): void {}
            public function getRememberTokenName(): string { return ''; }
            public function hasDirectGrant(string $key, ?string $panel): bool { return false; }
        };
    }

    private function makeUserWithGrant(int $id, string $key): Authenticatable
    {
        $key_ = $key;
        return new class($id, $key_) implements Authenticatable {
            public function __construct(private int $id, private string $grantKey) {}
            public function getAuthIdentifierName(): string { return 'id'; }
            public function getAuthIdentifier() { return $this->id; }
            public function getAuthPasswordName(): string { return 'password'; }
            public function getAuthPassword(): string { return ''; }
            public function getRememberToken(): string { return ''; }
            public function setRememberToken($value): void {}
            public function getRememberTokenName(): string { return ''; }
            public function hasDirectGrant(string $key, ?string $panel): bool
            {
                return $key === $this->grantKey;
            }
        };
    }

    private function makeRequest(?Authenticatable $user = null): Request
    {
        $request = Request::create('/test');
        if ($user !== null) {
            $request->setUserResolver(fn () => $user);
        }
        return $request;
    }

    // ------------------------------------------------------------------

    public function test_unauthenticated_gets_401(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $middleware = new CheckDirectGrant();
        $middleware->handle(
            $this->makeRequest(null),
            fn ($r) => new Response('ok'),
            'app.x.view',
            'app',
        );
    }

    public function test_user_without_grant_gets_403(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('app.x.view');

        $middleware = new CheckDirectGrant();
        $middleware->handle(
            $this->makeRequest($this->makeUser(1)),
            fn ($r) => new Response('ok'),
            'app.x.view',
            'app',
        );
    }

    public function test_user_with_grant_passes_through(): void
    {
        $user       = $this->makeUserWithGrant(2, 'app.x.view');
        $middleware  = new CheckDirectGrant();

        $response = $middleware->handle(
            $this->makeRequest($user),
            fn ($r) => new Response('ok', 200),
            'app.x.view',
            'app',
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_middleware_uses_null_panel_when_not_provided(): void
    {
        // Пользователь всегда проходит, если hasDirectGrant() = true
        $user = new class implements Authenticatable {
            public function getAuthIdentifierName(): string { return 'id'; }
            public function getAuthIdentifier() { return 99; }
            public function getAuthPasswordName(): string { return 'password'; }
            public function getAuthPassword(): string { return ''; }
            public function getRememberToken(): string { return ''; }
            public function setRememberToken($value): void {}
            public function getRememberTokenName(): string { return ''; }
            public function hasDirectGrant(string $key, ?string $panel): bool { return true; }
        };

        $middleware = new CheckDirectGrant();
        $response   = $middleware->handle(
            $this->makeRequest($user),
            fn ($r) => new Response('ok', 200),
            'any.key',
            // panel не передан
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_user_without_has_direct_grant_method_gets_403(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        // У пользователя нет метода hasDirectGrant — fallback false
        $user = new class implements Authenticatable {
            public function getAuthIdentifierName(): string { return 'id'; }
            public function getAuthIdentifier() { return 10; }
            public function getAuthPasswordName(): string { return 'password'; }
            public function getAuthPassword(): string { return ''; }
            public function getRememberToken(): string { return ''; }
            public function setRememberToken($value): void {}
            public function getRememberTokenName(): string { return ''; }
        };

        (new CheckDirectGrant())->handle(
            $this->makeRequest($user),
            fn ($r) => new Response('ok'),
            'app.x.view',
            'app',
        );
    }
}