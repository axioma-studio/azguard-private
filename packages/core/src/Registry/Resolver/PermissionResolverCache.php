<?php

declare(strict_types=1);

namespace AzGuard\Registry\Resolver;

use AzGuard\Registry\Values\PermissionSet;
use Closure;

/**
 * Per-request кэш PermissionSet.
 *
 * Поддерживает два слоя:
 * 1. In-memory (всегда): массив $requestCache, живёт один HTTP-запрос.
 * 2. Cross-request (опционально): Laravel cache store (Redis/etc.).
 *
 * Octane-safe: используется флаг $isLoading для предотвращения
 * concurrent stampede (паттерн из Spatie PermissionRegistrar).
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

        // Octane-safe: ждём если другой «поток» уже загружает
        if ($this->isLoading) {
            return $this->retryLoad($cacheKey, $callback);
        }

        $this->isLoading = true;

        try {
            // Cross-request cache (если настроен store != array)
            $cacheStore = config('az-guard.cache.store', 'array');

            if ($cacheStore !== 'array') {
                $ttl = config('az-guard.cache.expiration_time', 3600);
                $set = cache()->store($cacheStore)->remember(
                    $cacheKey,
                    $ttl,
                    fn () => $this->serialize($callback()),
                );

                // Десериализация если пришло из Redis
                if (is_array($set)) {
                    $set = PermissionSet::fromKeys($set);
                }
            } else {
                $set = $callback();
            }

            return $this->requestCache[$cacheKey] = $set;
        } finally {
            $this->isLoading = false;
        }
    }

    public function forgetForUser(int|string $userId, string $panelId): void
    {
        $prefix = "azguard.perms.{$userId}.{$panelId}";

        $this->requestCache = array_filter(
            $this->requestCache,
            static fn (string $k) => ! str_starts_with($k, $prefix),
            ARRAY_FILTER_USE_KEY,
        );

        $cacheStore = config('az-guard.cache.store', 'array');
        if ($cacheStore !== 'array') {
            cache()->store($cacheStore)->forget($prefix);
        }
    }

    public function forgetAll(): void
    {
        $this->requestCache = [];
    }

    /**
     * Ключ кэша для пользователя+панель.
     */
    public static function keyFor(int|string $userId, string $panelId): string
    {
        return "azguard.perms.{$userId}.{$panelId}";
    }

    private function retryLoad(string $cacheKey, Closure $callback, int $attempt = 0): PermissionSet
    {
        if ($attempt >= $this->maxRetries) {
            // Fallback: загрузить напрямую без кэша
            return $callback();
        }

        usleep(5_000); // 5ms

        if (isset($this->requestCache[$cacheKey])) {
            return $this->requestCache[$cacheKey];
        }

        return $this->retryLoad($cacheKey, $callback, $attempt + 1);
    }

    /**
     * Для cross-request cache: сохраняем массив ключей, не объект.
     *
     * @return list<string>
     */
    private function serialize(PermissionSet $set): array
    {
        return $set->toArray();
    }
}
