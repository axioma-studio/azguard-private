<?php

declare(strict_types=1);

namespace AzGuard\Registry\Resolver;

use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Contracts\PermissionCatalog;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Главная точка получения PermissionSet для пользователя.
 *
 * Агрегирует все GrantSource, фильтрует результат через PermissionCatalog
 * (только известные ключи или '*'), кэширует на request.
 *
 * Фаза 1: ClassRoleGrantSource.
 * Фаза 3: + DatabaseRoleGrantSource, DirectGrantSource.
 * Фаза 4: + ContextualRoleGrantSource.
 */
final class EffectivePermissionResolver
{
    /**
     * @param iterable<GrantSource> $sources
     */
    public function __construct(
        private readonly PermissionCatalog $catalog,
        private readonly iterable $sources,
        private readonly PermissionResolverCache $cache,
    ) {}

    public function forUser(Authenticatable $user, string $panelId): PermissionSet
    {
        $userId = $user->getAuthIdentifier();
        $cacheKey = PermissionResolverCache::keyFor($userId, $panelId);

        return $this->cache->rememberForRequest(
            $cacheKey,
            function () use ($user, $panelId): PermissionSet {
                return $this->resolve($user, $panelId);
            },
        );
    }

    private function resolve(Authenticatable $user, string $panelId): PermissionSet
    {
        // Сортируем источники по приоритету (desc)
        $sources = collect($this->sources)
            ->sortByDesc(fn (GrantSource $s) => $s->priority())
            ->all();

        $set = PermissionSet::empty();

        foreach ($sources as $source) {
            $granted = $source->permissionsFor($user, $panelId);
            $set = $set->merge($granted);

            // Если wildcard — дальше нет смысла
            if ($set->isWildcard()) {
                return $set;
            }
        }

        // Фильтрация через каталог: отбрасываем неизвестные ключи.
        // В debug-режиме можно логировать orphan keys.
        return $set->filter(
            fn (string $key) => $this->catalog->has($panelId, $key),
        );
    }

    /**
     * Сброс кэша для конкретного пользователя (вызывать при смене ролей).
     */
    public function forgetForUser(Authenticatable $user, string $panelId): void
    {
        $this->cache->forgetForUser($user->getAuthIdentifier(), $panelId);
    }
}
