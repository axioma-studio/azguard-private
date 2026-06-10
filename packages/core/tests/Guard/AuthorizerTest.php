<?php

declare(strict_types=1);

namespace AzGuard\Tests\Guard;

use AzGuard\Contracts\AzGuardManagerInterface;
use AzGuard\Grants\GrantBuilder;
use AzGuard\Guard\Authorizer;
use AzGuard\Models\DirectGrant;
use AzGuard\Registry\Contracts\GrantPriority;
use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Contracts\PermissionCatalog;
use AzGuard\Registry\Contracts\PermissionDefinition;
use AzGuard\Registry\Exceptions\InvalidPermissionKeyException;
use AzGuard\Registry\Resolver\EffectivePermissionResolver;
use AzGuard\Registry\Resolver\PermissionCache;
use AzGuard\Registry\Values\PermissionSet;
use AzGuard\Support\Panel;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use LogicException;
use PHPUnit\Framework\TestCase;
use UnitEnum;

final class AuthorizerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $app = new Container;
        $app->instance('config', new ConfigRepository(['az-guard' => ['cache' => ['store' => 'array']]]));
        Container::setInstance($app);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }

    private function makeUser(int $id = 1): Authenticatable&Authorizable
    {
        return new class($id) implements Authenticatable, Authorizable
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

            public function can($ability, $arguments = [])
            {
                return false;
            }

            public function cant($ability, $arguments = [])
            {
                return true;
            }

            public function cannot($ability, $arguments = [])
            {
                return true;
            }
        };
    }

    private function makeAuthorizer(PermissionSet $set, string $panelId = 'app'): Authorizer
    {
        $source = new class($set) implements GrantSource
        {
            public function __construct(private readonly PermissionSet $set) {}

            public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet
            {
                return $this->set;
            }

            public function priority(): GrantPriority
            {
                return GrantPriority::ClassRole;
            }
        };

        $catalog = new class implements PermissionCatalog
        {
            public function has(string $panelId, string $key): bool
            {
                return true;
            }

            public function all(string $panelId): array
            {
                return [];
            }

            public function get(string $panelId, string $resolvedKey): ?PermissionDefinition
            {
                return null;
            }

            public function assert(string $panelId, string $resolvedKey): PermissionDefinition
            {
                throw new InvalidPermissionKeyException($resolvedKey);
            }

            public function groups(string $panelId): array
            {
                return [];
            }

            public function panels(): array
            {
                return [];
            }
        };

        $resolver = new EffectivePermissionResolver(
            catalog: $catalog,
            sources: [$source],
            cache: new PermissionCache,
        );

        $panel = Panel::make()->id($panelId);

        $manager = new class($panelId, $panel) implements AzGuardManagerInterface
        {
            public function __construct(
                private string $panelId,
                private Panel $panel,
            ) {}

            public function registerPanel(Panel|callable $panel): void {}

            public function getPanels(): array
            {
                return [$this->panelId => $this->panel];
            }

            public function panel(string $id): ?Panel
            {
                return $this->panel;
            }

            public function currentPanel(): ?Panel
            {
                return $this->panel;
            }

            public function setCurrentPanel(?Panel $panel): void {}

            public function permission(string $panelId, string|UnitEnum $permission): string
            {
                return $panelId.'.'.(string) $permission;
            }

            public function forUser(Authenticatable $user): GrantBuilder
            {
                throw new LogicException('not implemented in stub');
            }

            public function grantDirect(Authenticatable $user, string $permissionKey, string $panelId, ?int $ttl): DirectGrant
            {
                throw new LogicException('not implemented in stub');
            }

            public function revokeDirect(Authenticatable $user, string $permissionKey, string $panelId): int
            {
                return 0;
            }

            public function activeGrants(Authenticatable $user, string $panelId): Collection
            {
                return new Collection;
            }
        };

        return new Authorizer($resolver, $manager);
    }

    public function test_returns_true_for_granted_permission(): void
    {
        $authorizer = $this->makeAuthorizer(
            PermissionSet::fromKeys(['app.documents.view']),
        );

        $result = $authorizer->check($this->makeUser(), 'app.documents.view');

        $this->assertTrue($result);
    }

    public function test_returns_null_for_missing_permission(): void
    {
        $authorizer = $this->makeAuthorizer(
            PermissionSet::fromKeys(['app.documents.view']),
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

        $nonAuth = new class implements Authorizable
        {
            public function can($ability, $arguments = [])
            {
                return false;
            }

            public function cant($ability, $arguments = [])
            {
                return true;
            }

            public function cannot($ability, $arguments = [])
            {
                return true;
            }
        };

        $result = $authorizer->check($nonAuth, 'app.x.view');

        $this->assertNull($result);
    }
}
