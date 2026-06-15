<?php

declare(strict_types=1);

namespace AzGuard\Registry\Resolver;

use AzGuard\Registry\Values\PermissionSet;
use AzGuard\Support\Config;
use Closure;

/**
 * Per-request (and optional cross-request) cache for PermissionSet.
 *
 * Supports two layers:
 * 1. In-memory (always): $requestCache 2D array — lives for one HTTP request.
 * 2. Cross-request (optional): Laravel cache store (Redis / file / etc.).
 *
 * Octane-safe ONLY when bound as `scoped` (see AzGuardServiceProvider): the
 * in-memory $requestCache must not survive across requests on a reused worker,
 * or one user's resolved permissions would bleed into the next request.
 * Concurrent in-process calls compute the value twice and last writer wins
 * in the array — harmless and far safer than any locking strategy.
 */
class PermissionCache
{
    /**
     * @var array<string, array<string, array<string, PermissionSet>>>
     *                                                                 userId => panelId => discriminator => PermissionSet
     */
    private array $requestCache = [];

    /**
     * The optional $discriminator distinguishes entries that depend on
     * out-of-band state (e.g. the active workspace context) so two contexts on
     * the same panel never share a cached set. Supplied by a PermissionLayer.
     */
    public function rememberForRequest(int|string $userId, string $panelId, Closure $callback, string $discriminator = ''): PermissionSet
    {
        $uid = (string) $userId;

        if (isset($this->requestCache[$uid][$panelId][$discriminator])) {
            return $this->requestCache[$uid][$panelId][$discriminator];
        }

        $store = Config::cacheStore();

        $set = $store !== 'array'
            ? $this->loadFromStore(self::keyFor($userId, $panelId, $discriminator), $store, $callback)
            : $callback();

        return $this->requestCache[$uid][$panelId][$discriminator] = $set;
    }

    public function forgetForUser(int|string $userId, string $panelId): void
    {
        $uid = (string) $userId;

        // Drop every discriminator (all contexts) for this user+panel in-process.
        unset($this->requestCache[$uid][$panelId]);

        $store = Config::cacheStore();

        if ($store !== 'array') {
            // Context-discriminated store entries (if any) are bounded by TTL —
            // a generic cache store cannot enumerate them by prefix.
            cache()->store($store)->forget(self::keyFor($userId, $panelId));
        }
    }

    public function forgetAll(): void
    {
        $this->requestCache = [];
    }

    public static function keyFor(int|string $userId, string $panelId, string $discriminator = ''): string
    {
        $base = "azguard.perms.{$userId}.{$panelId}";

        return $discriminator === '' ? $base : "{$base}.{$discriminator}";
    }

    private function loadFromStore(string $cacheKey, string $store, Closure $callback): PermissionSet
    {
        $raw = cache()->store($store)->remember(
            $cacheKey,
            Config::cacheTtl(),
            fn () => $callback()->keys(),
        );

        if (is_array($raw)) {
            return PermissionSet::fromKeys($raw);
        }

        // Stale or incompatible cache entry — recompute and overwrite.
        $set = $callback();
        cache()->store($store)->put($cacheKey, $set->keys(), Config::cacheTtl());

        return $set;
    }
}
