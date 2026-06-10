<?php

declare(strict_types=1);

namespace AzGuard\Registry\Resolver;

use AzGuard\Contracts\PermissionResolverInterface;
use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Contracts\PermissionCatalog;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;
use Override;

/**
 * Main entry point for obtaining a PermissionSet for a user.
 *
 * Aggregates all GrantSources, filters result through PermissionCatalog
 * (known keys only or '*'), caches per request via PermissionCache.
 *
 * Phase 1: ClassRoleGrantSource.
 * Phase 3: + DatabaseRoleGrantSource, DirectGrantSource.
 * Phase 4: + ContextualRoleGrantSource.
 */
final readonly class EffectivePermissionResolver implements PermissionResolverInterface
{
    /** @var list<GrantSource> Sorted by priority DESC once at construction time. */
    private array $sources;

    /**
     * @param  iterable<GrantSource>  $sources
     */
    public function __construct(
        private PermissionCatalog $catalog,
        iterable $sources,
        private PermissionCache $cache,
    ) {
        $this->sources = collect($sources)
            ->sortByDesc(fn (GrantSource $s): int => $s->priority()->value)
            ->values()
            ->all();
    }

    #[Override]
    public function forUser(Authenticatable $user, string $panelId): PermissionSet
    {
        return $this->cache->rememberForRequest(
            $user->getAuthIdentifier(),
            $panelId,
            fn (): PermissionSet => $this->resolve($user, $panelId),
        );
    }

    private function resolve(Authenticatable $user, string $panelId): PermissionSet
    {
        $set = PermissionSet::empty();

        foreach ($this->sources as $source) {
            $set = $set->merge($source->permissionsFor($user, $panelId));

            if ($set->isWildcard()) {
                return $set;
            }
        }

        return $set->filter(fn (string $key): bool => $this->catalog->has($panelId, $key));
    }

    /**
     * Flush cache for a specific user (call when roles change).
     */
    #[Override]
    public function forgetForUser(Authenticatable $user, string $panelId): void
    {
        $this->cache->forgetForUser($user->getAuthIdentifier(), $panelId);
    }
}
