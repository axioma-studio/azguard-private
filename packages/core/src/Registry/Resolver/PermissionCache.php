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
 * 1. In-memory (always): $requestCache array — lives for one HTTP request.
 * 2. Cross-request (optional): Laravel cache store (Redis / file / etc.).
 *
 * Octane-safe: no blocking or retry loops.
 * Concurrent in-process calls compute the value twice and last writer wins
 * in the array — harmless and far safer than any locking strategy.
 */
final class PermissionCache
{
    /** @var array<string, PermissionSet> */
    private array $requestCache = [];

    public function rememberForRequest(string $cacheKey, Closure $callback): PermissionSet
    {
        if (isset($this->requestCache[$cacheKey])) {
            return $this->requestCache[$cacheKey];
        }

        $store = Config::cacheStore();

        $set = $store !== 'array'
            ? $this->loadFromStore($cacheKey, $store, $callback)
            : $callback();

        return $this->requestCache[$cacheKey] = $set;
    }

    public function forgetForUser(int|string $userId, string $panelId): void
    {
        $prefix = self::keyFor($userId, $panelId);

        $this->requestCache = array_filter(
            $this->requestCache,
            static fn (string $k) => ! str_starts_with($k, $prefix),
            ARRAY_FILTER_USE_KEY,
        );

        $store = Config::cacheStore();

        if ($store !== 'array') {
            cache()->store($store)->forget($prefix);
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

        return is_array($raw) ? PermissionSet::fromKeys($raw) : $raw;
    }
}
