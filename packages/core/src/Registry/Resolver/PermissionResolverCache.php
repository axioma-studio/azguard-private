<?php

declare(strict_types=1);

namespace AzGuard\Registry\Resolver;

use AzGuard\Registry\Values\PermissionSet;
use AzGuard\Support\Config;
use Closure;

/**
 * Per-request cache for PermissionSet.
 *
 * Supports two layers:
 * 1. In-memory (always): $requestCache array, lives for one HTTP request.
 * 2. Cross-request (optional): Laravel cache store (Redis/etc.).
 *
 * Octane-safe: uses $isLoading flag to prevent concurrent stampede
 * (pattern from Spatie PermissionRegistrar).
 */
final class PermissionResolverCache
{
    /** @var array<string, PermissionSet> */
    private array $requestCache = [];

    private bool $isLoading = false;

    private int $maxRetries = 10;

    public function rememberForRequest(string $cacheKey, Closure $callback): PermissionSet
    {
        if (isset($this->requestCache[$cacheKey])) {
            return $this->requestCache[$cacheKey];
        }

        if ($this->isLoading) {
            return $this->retryLoad($cacheKey, $callback);
        }

        $this->isLoading = true;

        try {
            $store = Config::cacheStore();

            $set = match (true) {
                $store !== 'array' => $this->loadFromStore($cacheKey, $store, $callback),
                default            => $callback(),
            };

            return $this->requestCache[$cacheKey] = $set;
        } finally {
            $this->isLoading = false;
        }
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

    private function retryLoad(string $cacheKey, Closure $callback, int $attempt = 0): PermissionSet
    {
        if ($attempt >= $this->maxRetries) {
            return $callback();
        }

        usleep(5_000);

        return isset($this->requestCache[$cacheKey])
            ? $this->requestCache[$cacheKey]
            : $this->retryLoad($cacheKey, $callback, $attempt + 1);
    }
}
