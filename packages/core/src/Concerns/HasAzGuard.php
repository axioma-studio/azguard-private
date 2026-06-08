<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

use AzGuard\Events\RoleAttached;
use AzGuard\Events\RoleDetached;
use AzGuard\Models\Role;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

/**
 * Trait для User-модели. Предоставляет:
 * - связи roles() и azScopes()
 * - проверку прав hasAzPermission() и hasAzRole()
 * - кэширование прав (in-memory + опционально cross-request через cache store)
 * - API управления ролями: assignRole, removeRole, syncRoles, getRoleNames
 */
trait HasAzGuard
{
    /** @var Collection<int, string>|null */
    private ?Collection $azPermissionsCache = null;

    public function roles(): MorphToMany
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

        if (config('az-guard.features.wildcard_permission', false)) {
            foreach ($permissions as $p) {
                if (str_contains($p, '*')) {
                    $regex = '/^' . str_replace(['.', '*'], ['\\.', '.*'], $p) . '$/';
                    if (preg_match($regex, $permission)) {
                        return true;
                    }
                }
            }
        }

        return $permissions->contains($permission);
    }

    /**
     * Назначить одну или несколько ролей модели.
     *
     * @param  string|Role  ...$roles  Имя роли или экземпляр Role
     */
    public function assignRole(string|Role ...$roles): static
    {
        foreach ($roles as $role) {
            $roleModel = $this->resolveRole($role);

            if ($roleModel === null) {
                continue;
            }

            $this->roles()->syncWithoutDetaching([$roleModel->getKey()]);
            $this->clearAzPermissionsCache();
            event(new RoleAttached($this, $roleModel));
        }

        $this->unsetRelation('roles');

        return $this;
    }

    /**
     * Отозвать одну или несколько ролей у модели.
     *
     * @param  string|Role  ...$roles  Имя роли или экземпляр Role
     */
    public function removeRole(string|Role ...$roles): static
    {
        foreach ($roles as $role) {
            $roleModel = $this->resolveRole($role);

            if ($roleModel === null) {
                continue;
            }

            $this->roles()->detach($roleModel->getKey());
            $this->clearAzPermissionsCache();
            event(new RoleDetached($this, $roleModel));
        }

        $this->unsetRelation('roles');

        return $this;
    }

    /**
     * Синхронизировать набор ролей (удаляет старые, добавляет новые).
     *
     * @param  array<string|Role>  $roles
     */
    public function syncRoles(array $roles): static
    {
        $currentRoles = $this->roles()->get();

        foreach ($currentRoles as $currentRole) {
            $this->roles()->detach($currentRole->getKey());
            event(new RoleDetached($this, $currentRole));
        }

        foreach ($roles as $role) {
            $roleModel = $this->resolveRole($role);
            if ($roleModel === null) {
                continue;
            }
            $this->roles()->attach($roleModel->getKey());
            event(new RoleAttached($this, $roleModel));
        }

        $this->clearAzPermissionsCache();
        $this->unsetRelation('roles');

        return $this;
    }

    /**
     * Получить имена всех ролей пользователя.
     *
     * @return Collection<int, string>
     */
    public function getRoleNames(): Collection
    {
        return $this->roles->pluck('name');
    }

    /**
     * Загружает и кэширует все разрешения пользователя.
     * Использует cross-request кэш, если store != 'array'.
     *
     * @return Collection<int, string>
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
     * @return Collection<int, string>
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

    /**
     * Разрешает Role-объект из строки (по имени) или экземпляра Role.
     */
    protected function resolveRole(string|Role $role): ?Role
    {
        if ($role instanceof Role) {
            return $role;
        }

        /** @var class-string<Role> $roleClass */
        $roleClass = config('az-guard.models.role');

        return $roleClass::query()->where('name', $role)->first();
    }
}
