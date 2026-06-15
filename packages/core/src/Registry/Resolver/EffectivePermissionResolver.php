<?php

declare(strict_types=1);

namespace AzGuard\Registry\Resolver;

use AzGuard\Contracts\PermissionLayer;
use AzGuard\Contracts\PermissionResolverInterface;
use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Contracts\PermissionCatalog;
use AzGuard\Registry\Contracts\PermissionDefinition;
use AzGuard\Registry\Values\PermissionSet;
use AzGuard\Support\Config;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Override;
use Throwable;

/**
 * Main entry point for obtaining a PermissionSet for a user.
 *
 * Unions every GrantSource (highest priority first, short-circuiting on a
 * wildcard), applies the optional PermissionLayer (e.g. the context package),
 * filters the result through the PermissionCatalog (known keys only, or '*'),
 * and caches it per request via PermissionCache.
 */
final readonly class EffectivePermissionResolver implements PermissionResolverInterface
{
    /** @var list<GrantSource> Sorted by priority DESC once at construction time. */
    private array $sources;

    /**
     * @param  iterable<GrantSource>  $sources
     * @param  PermissionLayer|null  $layer  Optional post-aggregation hook
     *                                       (e.g. the context package). Null = no layer.
     */
    public function __construct(
        private PermissionCatalog $catalog,
        iterable $sources,
        private PermissionCache $cache,
        private ?PermissionLayer $layer = null,
    ) {
        $this->sources = collect($sources)
            ->sortByDesc(fn (GrantSource $s): int => $s->priority())
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
            $this->layer?->cacheDiscriminator($panelId) ?? '',
        );
    }

    private function resolve(Authenticatable $user, string $panelId): PermissionSet
    {
        $set = PermissionSet::empty();

        foreach ($this->sources as $source) {
            try {
                $set = $set->merge($source->permissionsFor($user, $panelId));
            } catch (Throwable $e) {
                if (Config::failOnSourceException()) {
                    throw $e;
                }

                Log::warning('AzGuard: grant source failed, skipping', [
                    'source' => $source::class,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            if ($set->isWildcard()) {
                return $set;
            }
        }

        // Optional post-aggregation layer (e.g. the context package applying its
        // merge strategy to the global set). Skipped on a global wildcard above —
        // a superadmin transcends contextual narrowing.
        if ($this->layer instanceof PermissionLayer) {
            $set = $this->layer->apply($set, $user, $panelId);

            if ($set->isWildcard()) {
                return $set;
            }
        }

        if (! in_array($panelId, $this->catalog->panels(), true)) {
            return $set;
        }

        return $this->filterAgainstCatalog($set, $panelId);
    }

    /**
     * Drop keys the catalog does not know.
     *
     * Exact keys must exist in the catalog. Wildcard patterns ('app.docs.*')
     * are kept only when the wildcard feature is enabled AND the pattern
     * actually covers at least one catalog key — so a meaningful grant survives
     * but a stale 'app.nonsense.*' that matches nothing is dropped. With the
     * feature off, patterns are treated as unknown exact keys and removed.
     */
    private function filterAgainstCatalog(PermissionSet $set, string $panelId): PermissionSet
    {
        if (! Config::wildcardEnabled()) {
            return $set->filter(fn (string $key): bool => $this->catalog->has($panelId, $key));
        }

        $catalogKeys = array_map(
            static fn (PermissionDefinition $d): string => $d->key(),
            $this->catalog->all($panelId),
        );

        return $set->filter(function (string $key) use ($panelId, $catalogKeys): bool {
            if (! str_contains($key, '*')) {
                return $this->catalog->has($panelId, $key);
            }

            $pattern = PermissionSet::fromKeys([$key]);

            foreach ($catalogKeys as $catalogKey) {
                if ($pattern->matchesWildcard($catalogKey)) {
                    return true;
                }
            }

            return false;
        });
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
