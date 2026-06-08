<?php

declare(strict_types=1);

namespace AzGuard\Tests\Guard;

use AzGuard\AzGuardManager;
use AzGuard\Guard\Authorizer;
use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Contracts\PermissionCatalog;
use AzGuard\Registry\Resolver\EffectivePermissionResolver;
use AzGuard\Registry\Resolver\PermissionResolverCache;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable;
use PHPUnit\Framework\TestCase;

final class AuthorizerTest extends TestCase
{
    private function makeUser(int $id = 1): Authenticatable&Authorizable
    {
        return new class($id) implements Authenticatable, Authorizable {
            public function __construct(private int $id) {}
            public function getAuthIdentifierName(): string { return 'id'; }
            public function getAuthIdentifier() { return $this->id; }
            public function getAuthPasswordName(): string { return 'password'; }
            public function getAuthPassword(): string { return ''; }
            public function getRememberToken(): string { return ''; }
            public function setRememberToken($value): void {}
            public function getRememberTokenName(): string { return ''; }
            public function can($ability, $arguments = []) { return false; }
            public function cant($ability, $arguments = []) { return true; }
            public function cannot($ability, $arguments = []) { return true; }
        };
    }

    private function makeAuthorizer(PermissionSet $set, string $panelId = 'app'): Authorizer
    {
        $source = new class($set) implements GrantSource {
            public function __construct(private readonly PermissionSet $set) {}
            public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet { return $this->set; }
            public function priority(): int { return 100; }
        };

        $catalog = new class implements PermissionCatalog {
            public function has(string $panelId, string $key): bool { return true; }
            public function all(string $panelId): array { return []; }
        };

        $resolver = new EffectivePermissionResolver(
            catalog: $catalog,
            sources: [$source],
            cache: new PermissionResolverCache,
        );

        $manager = new class($panelId) extends AzGuardManager {
            public function __construct(private string $panel) {}
            public function currentPanel(): ?\AzGuard\Panels\PanelDefinition
            {
                $p = new \AzGuard\Panels\PanelDefinition($this->panel, []);
                return $p;
            }
            public function getPanels(): array { return [$this->panel => $this->currentPanel()]; }
        };

        app()->instance(AzGuardManager::class, $manager);

        return new Authorizer($resolver);
    }

    public function test_returns_true_for_granted_permission(): void
    {
        $authorizer = $this->makeAuthorizer(
            PermissionSet::fromKeys(['app.documents.view'])
        );

        $result = $authorizer->check($this->makeUser(), 'app.documents.view');

        $this->assertTrue($result);
    }

    public function test_returns_null_for_missing_permission(): void
    {
        $authorizer = $this->makeAuthorizer(
            PermissionSet::fromKeys(['app.documents.view'])
        );

        $result = $authorizer->check($this->makeUser(), 'app.documents.delete');

        $this->assertNull($result);
    }

    public function test_returns_true_for_wildcard_superadmin(): void
    {
        $authorizer = $this->makeAuthorizer(PermissionSet::wildcard());

        $result = $authorizer->check($this->makeUser(), 'app.anything.at.all');

        $this->assertTrue($result);
    }

    public function test_returns_null_for_non_authenticatable(): void
    {
        $authorizer = $this->makeAuthorizer(PermissionSet::empty());

        $nonAuth = new class implements Authorizable {
            public function can($ability, $arguments = []) { return false; }
            public function cant($ability, $arguments = []) { return true; }
            public function cannot($ability, $arguments = []) { return true; }
        };

        $result = $authorizer->check($nonAuth, 'app.x.view');

        $this->assertNull($result);
    }
}
