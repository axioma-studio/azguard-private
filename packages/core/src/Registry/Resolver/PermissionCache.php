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
 * 1. In-memory (always): $requestCache 2D array — lives for one HTTP request.
 * 2. Cross-request (optional): Laravel cache store (Redis / file / etc.).
 *
 * Octane-safe: no blocking or retry loops.
 * Concurrent in-process calls compute the value twice and last writer wins
 * in the array — harmless and far safer than any locking strategy.
 */
class PermissionCache
{
    /** @var array<string, array<string, PermissionSet>> userId => panelId => PermissionSet */
    private array $requestCache = [];

    public function rememberForRequest(int|string $userId, string $panelId, Closure $callback): PermissionSet
    {
        $uid = (string) $userId;

        if (isset($this->requestCache[$uid][$panelId])) {
            return $this->requestCache[$uid][$panelId];
        }

        $store = Config::cacheStore();

        $set = $store !== 'array'
            ? $this->loadFromStore(self::keyFor($userId, $panelId), $store, $callback)
            : $callback();

        return $this->requestCache[$uid][$panelId] = $set;
    }

    public function forgetForUser(int|string $userId, string $panelId): void
    {
        $uid = (string) $userId;

        unset($this->requestCache[$uid][$panelId]);

        $store = Config::cacheStore();

        if ($store !== 'array') {
            cache()->store($store)->forget(self::keyFor($userId, $panelId));
        }
    }

    public function forgetAll(): void
    {
        $this->requestCache = [];
    }

    public static function keyFor(int|string $userId, string $panelId): string
    {
        return "azguard.perms.{$userId}.{$panelId}";
    }

    private function loadFromStore(string $cacheKey, string $store, Closure $callback): PermissionSet
    {
        $raw = cache()->store($store)->remember(
            $cacheKey,
            Config::cacheTtl(),
            fn () => $callback()->toArray(),
        );

        if (is_array($raw)) {
            return PermissionSet::fromKeys($raw);
        }

        // Stale or incompatible cache entry — recompute and overwrite.
        $set = $callback();
        cache()->store($store)->put($cacheKey, $set->toArray(), Config::cacheTtl());

        return $set;
    }
}
