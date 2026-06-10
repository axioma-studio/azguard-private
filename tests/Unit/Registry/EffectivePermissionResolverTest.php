<?php

declare(strict_types=1);

use AzGuard\Registry\Contracts\GrantPriority;
use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Contracts\PermissionCatalog;
use AzGuard\Registry\Contracts\PermissionDefinition;
use AzGuard\Registry\Resolver\EffectivePermissionResolver;
use AzGuard\Registry\Resolver\PermissionResolverCache;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;

// ─── Helpers ──────────────────────────────────────────────────────────────

function makeUser(int $id = 1): Authenticatable
{
    return new class($id) implements Authenticatable
    {
        public function __construct(private int $id) {}

        public function getAuthIdentifierName(): string
        {
            return 'id';
        }

        public function getAuthIdentifier(): int
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

        public function getRememberToken(): ?string
        {
            return null;
        }

        public function setRememberToken($value): void {}

        public function getRememberTokenName(): string
        {
            return 'remember_token';
        }
    };
}

function makeGrantSource(PermissionSet $set, GrantPriority $priority = GrantPriority::ClassRole): GrantSource
{
    return new class($set, $priority) implements GrantSource
    {
        public function __construct(private PermissionSet $s, private GrantPriority $p) {}

        public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet
        {
            return $this->s;
        }

        public function priority(): GrantPriority
        {
            return $this->p;
        }
    };
}

function makeCatalog(array $knownKeys, array $panels = ['app']): PermissionCatalog
{
    return new class($knownKeys, $panels) implements PermissionCatalog
    {
        public function __construct(private array $keys, private array $panelList) {}

        public function all(string $panelId): array
        {
            return [];
        }

        public function has(string $panelId, string $key): bool
        {
            return in_array($key, $this->keys, true);
        }

        public function get(string $panelId, string $key): ?PermissionDefinition
        {
            return null;
        }

        public function assert(string $panelId, string $key): PermissionDefinition
        {
            throw new RuntimeException;
        }

        public function groups(string $panelId): array
        {
            return [];
        }

        public function panels(): array
        {
            return $this->panelList;
        }
    };
}

// ─── Tests ────────────────────────────────────────────────────────────────

describe('EffectivePermissionResolver', function () {

    it('returns empty set when no sources', function () {
        $resolver = new EffectivePermissionResolver(
            catalog: makeCatalog([]),
            sources: [],
            cache: new PermissionResolverCache,
        );

        $set = $resolver->forUser(makeUser(1), 'app');

        expect($set->isEmpty())->toBeTrue();
    });

    it('merges permissions from multiple sources', function () {
        $sourceA = makeGrantSource(PermissionSet::fromKeys(['app.posts.view']), GrantPriority::ClassRole);
        $sourceB = makeGrantSource(PermissionSet::fromKeys(['app.posts.edit']), GrantPriority::DatabaseRole);

        $resolver = new EffectivePermissionResolver(
            catalog: makeCatalog(['app.posts.view', 'app.posts.edit']),
            sources: [$sourceA, $sourceB],
            cache: new PermissionResolverCache,
        );

        $set = $resolver->forUser(makeUser(1), 'app');

        expect($set->has('app.posts.view'))->toBeTrue()
            ->and($set->has('app.posts.edit'))->toBeTrue();
    });

    it('stops early and returns wildcard when any source grants *', function () {
        $calls = 0;
        $sourceWild = makeGrantSource(PermissionSet::wildcard(), GrantPriority::ClassRole);
        $sourceLow = makeGrantSource(PermissionSet::fromKeys(['app.posts.view']), GrantPriority::DirectGrant);

        $resolver = new EffectivePermissionResolver(
            catalog: makeCatalog(['app.posts.view']),
            sources: [$sourceWild, $sourceLow],
            cache: new PermissionResolverCache,
        );

        $set = $resolver->forUser(makeUser(1), 'app');

        expect($set->isWildcard())->toBeTrue();
    });

    it('filters out keys not in catalog', function () {
        // Source даёт 3 ключа, но каталог знает только 2
        $source = makeGrantSource(PermissionSet::fromKeys([
            'app.posts.view',
            'app.posts.edit',
            'app.orphan.unknown',   // не зарегистрировано
        ]));

        $resolver = new EffectivePermissionResolver(
            catalog: makeCatalog(['app.posts.view', 'app.posts.edit']),
            sources: [$source],
            cache: new PermissionResolverCache,
        );

        $set = $resolver->forUser(makeUser(1), 'app');

        expect($set->toArray())->toBe(['app.posts.view', 'app.posts.edit'])
            ->and($set->has('app.orphan.unknown'))->toBeFalse();
    });

    it('wildcard set is NOT filtered through catalog', function () {
        $source = makeGrantSource(PermissionSet::wildcard());

        $resolver = new EffectivePermissionResolver(
            catalog: makeCatalog([]),   // каталог пустой — не важно
            sources: [$source],
            cache: new PermissionResolverCache,
        );

        $set = $resolver->forUser(makeUser(1), 'app');

        expect($set->isWildcard())->toBeTrue();
    });

    it('caches result and does not call sources twice for same user+panel', function () {
        $callCount = 0;

        $source = new class($callCount) implements GrantSource
        {
            public function __construct(private int &$count) {}

            public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet
            {
                $this->count++;

                return PermissionSet::fromKeys(['app.posts.view']);
            }

            public function priority(): GrantPriority
            {
                return GrantPriority::ClassRole;
            }
        };

        $resolver = new EffectivePermissionResolver(
            catalog: makeCatalog(['app.posts.view']),
            sources: [$source],
            cache: new PermissionResolverCache,
        );

        $user = makeUser(1);
        $resolver->forUser($user, 'app');
        $resolver->forUser($user, 'app');

        expect($callCount)->toBe(1);
    });

    it('sources are sorted by priority descending', function () {
        $order = [];

        $makeOrderedSource = function (GrantPriority $priority, string $key) use (&$order): GrantSource {
            return new class($priority, $key, $order) implements GrantSource
            {
                public function __construct(
                    private GrantPriority $p,
                    private string $k,
                    private array &$o,
                ) {}

                public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet
                {
                    $this->o[] = $this->p->value;

                    return PermissionSet::fromKeys([$this->k]);
                }

                public function priority(): GrantPriority
                {
                    return $this->p;
                }
            };
        };

        $resolver = new EffectivePermissionResolver(
            catalog: makeCatalog(['app.a', 'app.b', 'app.c']),
            sources: [
                $makeOrderedSource(GrantPriority::DatabaseRole, 'app.b'),
                $makeOrderedSource(GrantPriority::ClassRole, 'app.a'),
                $makeOrderedSource(GrantPriority::DirectGrant, 'app.c'),
            ],
            cache: new PermissionResolverCache,
        );

        $resolver->forUser(makeUser(1), 'app');

        expect($order)->toBe([100, 90, 80]);
    });
});
