<?php

declare(strict_types=1);

namespace AzGuard\Registry\Resolver;

use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Contracts\PermissionCatalog;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Main entry point for obtaining a PermissionSet for a user.
 *
 * Aggregates all GrantSources, filters result through PermissionCatalog
 * (known keys only or '*'), caches per request.
 *
 * Phase 1: ClassRoleGrantSource.
 * Phase 3: + DatabaseRoleGrantSource, DirectGrantSource.
 * Phase 4: + ContextualRoleGrantSource.
 */
final class EffectivePermissionResolver
{
    /** @var list<GrantSource> Sorted by priority DESC once at construction time. */
    private readonly array $sources;

    /**
     * @param iterable<GrantSource> $sources
     */
    public function __construct(
        private readonly PermissionCatalog $catalog,
        iterable $sources,
        private readonly PermissionResolverCache $cache,
    ) {
        // Sort once at construction — priorities are static.
        $this->sources = collect($sources)
            ->sortByDesc(fn (GrantSource $s) => $s->priority())
            ->values()
            ->all();
    }

    public function forUser(Authenticatable $user, string $panelId): PermissionSet
    {
        $cacheKey = PermissionResolverCache::keyFor($user->getAuthIdentifier(), $panelId);

        return $this->cache->rememberForRequest(
            $cacheKey,
            fn () => $this->resolve($user, $panelId),
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

        return $set->filter(fn (string $key) => $this->catalog->has($panelId, $key));
    }

    /**
     * Flush cache for a specific user (call when roles change).
     */
    public function forgetForUser(Authenticatable $user, string $panelId): void
    {
        $this->cache->forgetForUser($user->getAuthIdentifier(), $panelId);
    }
}
