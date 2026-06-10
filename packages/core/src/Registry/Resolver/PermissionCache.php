<?php

declare(strict_types=1);

namespace AzGuard\Registry\Resolver;

use AzGuard\Registry\Values\PermissionSet;
use AzGuard\Support\Config;
use Closure;

/**
 * Per-request (and optional cross-request) cache for PermissionSet.
 *
 * Renamed from PermissionResolverCache — the class caches permissions,
 * not the resolver itself. PermissionResolverCache is kept as a BC alias.
 *
 * Supports two layers:
 * 1. In-memory (always): 2D $requestCache[$userId][$panelId] — lives for one HTTP request.
 *    Using a 2D array allows O(1) full-user invalidation via unset($cache[$userId])
 *    instead of iterating and filtering by key prefix.
 * 2. Cross-request (optional): Laravel cache store (Redis / file / etc.).
 *
 * Octane-safe: no blocking or retry loops.
 * Concurrent in-process calls compute the value twice and last writer wins
 * in the array — harmless and far safer than any locking strategy.
 */
final class PermissionCache
{
    /**
     * 2D in-memory cache: [userId => [panelId => PermissionSet]].
     *
     * @var array<int|string, array<string, PermissionSet>>
     */
    private array $requestCache = [];

    public function rememberForRequest(string $cacheKey, Closure $callback): PermissionSet
    {
        // Decompose the canonical key to address the 2D cache.
        [$userId, $panelId] = $this->decomposeCacheKey($cacheKey);

        if (isset($this->requestCache[$userId][$panelId])) {
            return $this->requestCache[$userId][$panelId];
        }

        $store = Config::cacheStore();

        $set = $store !== 'array'
            ? $this->loadFromStore($cacheKey, $store, $callback)
            : $callback();

        return $this->requestCache[$userId][$panelId] = $set;
    }

    /**
     * Invalidate the in-memory + persistent cache for a specific user+panel.
     */
    public function forgetForUser(int|string $userId, string $panelId): void
    {
        // O(1) removal from the 2D array — no iteration needed.
        unset($this->requestCache[$userId][$panelId]);

        $store = Config::cacheStore();

        if ($store !== 'array') {
            cache()->store($store)->forget(self::keyFor($userId, $panelId));
        }
    }

    /**
     * Flush the entire in-memory cache.
     * Persistent store is NOT flushed here — use CacheResetCommand for that.
     */
    public function forgetAll(): void
    {
        $this->requestCache = [];
    }

    public static function keyFor(int|string $userId, string $panelId): string
    {
        return "azguard.perms.{$userId}.{$panelId}";
    }

    /**
     * Decompose a canonical cache key back into its components.
     *
     * Key format: "azguard.perms.{userId}.{panelId}"
     * The userId segment may itself contain dots (e.g. UUID strings),
     * so we strip the known prefix and suffix.
     *
     * @return array{0: string, 1: string}
     */
    private function decomposeCacheKey(string $cacheKey): array
    {
        // Strip "azguard.perms." prefix (14 chars)
        $remainder = substr($cacheKey, 14);

        // panelId never contains dots by convention (e.g. 'app', 'admin').
        $lastDot = strrpos($remainder, '.');

        if ($lastDot === false) {
            // Fallback: treat the whole remainder as userId with empty panelId.
            return [$remainder, ''];
        }

        return [
            substr($remainder, 0, $lastDot),
            substr($remainder, $lastDot + 1),
        ];
    }

    private function loadFromStore(string $cacheKey, string $store, Closure $callback): PermissionSet
    {
        $raw = cache()->store($store)->remember(
            $cacheKey,
            Config::cacheTtl(),
            fn () => $callback()->keys(),
        );

        if ($raw instanceof PermissionSet) {
            return $raw;
        }

        if (is_array($raw)) {
            return PermissionSet::fromKeys($raw);
        }

        // Fallback: cache returned unexpected type (e.g. after a driver change).
        // Recompute rather than returning corrupt data.
        return $callback();
    }
}
