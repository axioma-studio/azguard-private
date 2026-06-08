<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

use Illuminate\Support\Collection;

/**
 * Trait для User-модели. Предоставляет:
 * - связи roles() и azScopes()
 * - проверку прав hasAzPermission() и hasAzRole()
 * - кэширование прав (in-memory + опционально cross-request через cache store)
 */
trait HasAzGuard
{
    /** @var \Illuminate\Support\Collection<int, string>|null */
    private ?Collection $azPermissionsCache = null;

    public function roles()
    {
        return $this->morphToMany(
            config('az-guard.models.role'),
            'model',
            config('az-guard.table_names.model_has_roles')
        );
    }

    public function azScopes()
    {
        return $this->morphMany(config('az-guard.models.scope'), 'model');
    }

    public function hasAzRole(string $name): bool
    {
        return $this->roles->contains('name', $name);
    }

    /**
     * Основной метод для политик.
     * Проверяет, даёт ли хоть одна роль пользователя указанное разрешение.
     */
    public function hasAzPermission(string $permission): bool
    {
        $permissions = $this->getAzPermissions();

        if ($permissions->contains('*')) {
            return true;
        }

        // Wildcard-паттерны (если включены)
        if (config('az-guard.features.wildcard_permission', false)) {
            foreach ($permissions as $p) {
                if (str_contains($p, '*')) {
                    $regex = '/^' . str_replace(['.', '*'], ['\.', '.*'], $p) . '$/';
                    if (preg_match($regex, $permission)) {
                        return true;
                    }
                }
            }
        }

        return $permissions->contains($permission);
    }

    /**
     * Загружает и кэширует все разрешения пользователя.
     * Использует cross-request кэш, если store != 'array'.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function getAzPermissions(): Collection
    {
        if ($this->azPermissionsCache !== null) {
            return $this->azPermissionsCache;
        }

        $cacheStore = config('az-guard.cache.store', 'array');
        $useCrossRequestCache = $cacheStore !== 'array' && $this->getKey() !== null;

        if ($useCrossRequestCache) {
            $cacheKey = config('az-guard.cache.key', 'azguard.permissions') . '.' . $this->getKey();
            $ttl = config('az-guard.cache.expiration_time', 3600);

            $this->azPermissionsCache = cache()
                ->store($cacheStore)
                ->remember($cacheKey, $ttl, fn (): Collection => $this->loadAzPermissions());
        } else {
            $this->azPermissionsCache = $this->loadAzPermissions();
        }

        return $this->azPermissionsCache;
    }

    /**
     * Сбрасывает кэш прав пользователя (in-memory + persistent).
     */
    public function clearAzPermissionsCache(): void
    {
        $this->azPermissionsCache = null;

        $cacheStore = config('az-guard.cache.store', 'array');

        if ($cacheStore !== 'array' && $this->getKey() !== null) {
            $cacheKey = config('az-guard.cache.key', 'azguard.permissions') . '.' . $this->getKey();
            cache()->store($cacheStore)->forget($cacheKey);
        }
    }

    /**
     * Непосредственная загрузка прав из ролей пользователя.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    protected function loadAzPermissions(): Collection
    {
        return $this->roles
            ->map(fn ($roleModel) => $roleModel->getRoleLogic())
            ->filter()
            ->flatMap(function ($roleLogic): array {
                $permissions = $roleLogic->permissions();

                return is_array($permissions) ? $permissions : [];
            })
            ->unique()
            ->values();
    }
}
