<?php

declare(strict_types=1);

namespace AzGuard\Abilities;

use Illuminate\Support\Facades\Cache;

trait InteractsWithAbilitiesCache
{
    /**
     * @param  array<int, mixed>  $keyParts
     */
    protected static function buildCacheKey(string $prefix, array $keyParts): string
    {
        return $prefix.':'.implode(separator: ':', array: array_map(
            callback: static fn (mixed $part): string => is_object($part) && method_exists($part, 'getKey')
                ? (string) $part->getKey()
                : (string) $part,
            array: $keyParts,
        ));
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    protected static function rememberAbilities(string $key, callable $callback, int $ttlSeconds = 60): mixed
    {
        return Cache::remember(
            key: $key,
            ttl: $ttlSeconds,
            callback: $callback,
        );
    }
}
