<?php

declare(strict_types=1);

namespace AzGuard\Tests\Registry;

use AzGuard\Registry\Builders\CompositePermissionCatalog;
use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Contracts\PermissionCatalog;
use AzGuard\Registry\Definitions\EnumPermissionDefinition;
use AzGuard\Registry\Resolver\EffectivePermissionResolver;
use AzGuard\Registry\Resolver\PermissionCache;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;
use PHPUnit\Framework\TestCase;

enum ResolverTestPermission: string
{
    case View = 'view';
    case Create = 'create';
    case Delete = 'delete';
}

final class EffectivePermissionResolverTest extends TestCase
{
    private function makeUser(int $id = 1): Authenticatable
    {
        return new class($id) implements Authenticatable
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
        };
    }

    private function makeGrantSource(PermissionSet $set, int $priority = 100): GrantSource
    {
        return new class($set, $priority) implements GrantSource
        {
            public function __construct(
                private readonly PermissionSet $set,
                private readonly int $prio,
            ) {}

            public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet
            {
                return $this->set;
            }

            public function priority(): int
            {
                return $this->prio;
            }
        };
    }

    private function makeCatalog(array $definitions, string $panelId = 'app'): PermissionCatalog
    {
        $builder = new class($definitions) implements \AzGuard\Registry\Contracts\PermissionCatalogBuilder
        {
            public function __construct(private readonly array $defs) {}

            public function build(string $panelId): array
            {
                return $this->defs;
            }

            public function supports(string $panelId): bool
            {
                return true;
            }
        };

        return new CompositePermissionCatalog(
            builders: [$builder],
            panelIds: [$panelId],
        );
    }

    public function test_resolves_permissions_from_source(): void
    {
        $def = EnumPermissionDefinition::fromCase(ResolverTestPermission::View, 'app', 'app.docs.view');
        $catalog = $this->makeCatalog([$def]);

        $source = $this->makeGrantSource(PermissionSet::fromKeys(['app.docs.view']));

        $resolver = new EffectivePermissionResolver(
            catalog: $catalog,
            sources: [$source],
            cache: new PermissionCache,
        );

        $set = $resolver->forUser($this->makeUser(), 'app');

        $this->assertTrue($set->grants('app.docs.view'));
    }

    public function test_filters_out_orphan_keys(): void
    {
        // Каталог содержит только 'app.docs.view'
        $def = EnumPermissionDefinition::fromCase(ResolverTestPermission::View, 'app', 'app.docs.view');
        $catalog = $this->makeCatalog([$def]);

        // Source выдаёт также orphan-ключ
        $source = $this->makeGrantSource(
            PermissionSet::fromKeys(['app.docs.view', 'app.orphan.key'])
        );

        $resolver = new EffectivePermissionResolver(
            catalog: $catalog,
            sources: [$source],
            cache: new PermissionCache,
        );

        $set = $resolver->forUser($this->makeUser(), 'app');

        $this->assertTrue($set->grants('app.docs.view'));
        $this->assertFalse($set->grants('app.orphan.key'));
    }

    public function test_wildcard_source_skips_catalog_filter(): void
    {
        $catalog = $this->makeCatalog([]);
        $source = $this->makeGrantSource(PermissionSet::wildcard());

        $resolver = new EffectivePermissionResolver(
            catalog: $catalog,
            sources: [$source],
            cache: new PermissionCache,
        );

        $set = $resolver->forUser($this->makeUser(), 'app');

        $this->assertTrue($set->isWildcard());
        $this->assertTrue($set->grants('anything'));
    }

    public function test_higher_priority_source_processed_first(): void
    {
        $def = EnumPermissionDefinition::fromCase(ResolverTestPermission::View, 'app', 'app.x.view');
        $catalog = $this->makeCatalog([$def]);

        $lowSource = $this->makeGrantSource(PermissionSet::fromKeys(['app.x.view']), priority: 10);
        $highSource = $this->makeGrantSource(PermissionSet::wildcard(), priority: 100);

        $resolver = new EffectivePermissionResolver(
            catalog: $catalog,
            sources: [$lowSource, $highSource], // намеренно неотсортированы
            cache: new PermissionCache,
        );

        // Высший приоритет = wildcard, итог должен быть wildcard
        $set = $resolver->forUser($this->makeUser(), 'app');
        $this->assertTrue($set->isWildcard());
    }

    public function test_result_cached_for_same_user_and_panel(): void
    {
        $def = EnumPermissionDefinition::fromCase(ResolverTestPermission::View, 'app', 'app.docs.view');
        $catalog = $this->makeCatalog([$def]);

        $callCount = 0;
        $source = new class($callCount) implements GrantSource
        {
            public function __construct(private int &$count) {}

            public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet
            {
                $this->count++;

                return PermissionSet::fromKeys(['app.docs.view']);
            }

            public function priority(): int
            {
                return 100;
            }
        };

        $resolver = new EffectivePermissionResolver(
            catalog: $catalog,
            sources: [$source],
            cache: new PermissionCache,
        );

        $user = $this->makeUser(42);
        $resolver->forUser($user, 'app');
        $resolver->forUser($user, 'app'); // повторный вызов

        // Source вызван только один раз
        $this->assertSame(1, $callCount);
    }

    public function test_forget_invalidates_cache(): void
    {
        $def = EnumPermissionDefinition::fromCase(ResolverTestPermission::View, 'app', 'app.docs.view');
        $catalog = $this->makeCatalog([$def]);

        $callCount = 0;
        $source = new class($callCount) implements GrantSource
        {
            public function __construct(private int &$count) {}

            public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet
            {
                $this->count++;

                return PermissionSet::fromKeys(['app.docs.view']);
            }

            public function priority(): int
            {
                return 100;
            }
        };

        $resolver = new EffectivePermissionResolver(
            catalog: $catalog,
            sources: [$source],
            cache: new PermissionCache,
        );

        $user = $this->makeUser(7);
        $resolver->forUser($user, 'app');
        $resolver->forgetForUser($user, 'app');
        $resolver->forUser($user, 'app'); // после сброса — повторная загрузка

        $this->assertSame(2, $callCount);
    }
}
