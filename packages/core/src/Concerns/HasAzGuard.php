<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

use AzGuard\Events\RoleAttached;
use AzGuard\Events\RoleDetached;
use AzGuard\Models\Role;
use AzGuard\Registry\Resolver\EffectivePermissionResolver;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

/**
 * Trait для User-модели. Предоставляет:
 * - связи roles() и azScopes()
 * - проверку прав hasAzPermission() и hasAzRole()
 * - кэширование прав делегируется EffectivePermissionResolver
 * - API управления ролями: assignRole, removeRole, syncRoles, getRoleNames
 */
trait HasAzGuard
{
    /**
     * @deprecated Используйте EffectivePermissionResolver напрямую или через hasAzPermission().
     * Оставлен для обратной совместимости; будет удалён в следующей мажорной версии.
     * @var Collection<int, string>|null
     */
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
     * Делегирует в EffectivePermissionResolver (через PermissionSet::grants).
     *
     * Бросает исключение если ключ не зарегистрирован в каталоге
     * (только в strict-режиме; по умолчанию — тихо false).
     */
    public function hasAzPermission(string $permission, string $panelId = 'app'): bool
    {
        return $this->getAzPermissionSet($panelId)->grants($permission);
    }

    /**
     * Тихая версия: никогда не бросает исключений.
     * Используйте в Blade-условиях и UI.
     */
    public function checkAzPermission(string $permission, string $panelId = 'app'): bool
    {
        try {
            return $this->hasAzPermission($permission, $panelId);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Получить PermissionSet для панели.
     * Все кэширование — в EffectivePermissionResolver.
     */
    public function getAzPermissionSet(string $panelId = 'app'): PermissionSet
    {
        return app(EffectivePermissionResolver::class)->forUser($this, $panelId);
    }

    /**
     * @deprecated Используйте getAzPermissionSet()->toArray().
     * Оставлен для обратной совместимости.
     *
     * @return Collection<int, string>
     */
    public function getAzPermissions(string $panelId = 'app'): Collection
    {
        return collect($this->getAzPermissionSet($panelId)->toArray());
    }

    /**
     * Сброс кэша прав пользователя.
     * Вызывается автоматически при assignRole/removeRole/syncRoles.
     */
    public function clearAzPermissionsCache(string $panelId = 'app'): void
    {
        $this->azPermissionsCache = null;
        app(EffectivePermissionResolver::class)->forgetForUser($this, $panelId);
    }

    /**
     * Назначить одну или несколько ролей модели.
     *
     * @param  string|Role  ...$roles
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
     * @param  string|Role  ...$roles
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
     * Синхронизировать набор ролей.
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

    /**
     * @deprecated Используйте clearAzPermissionsCache().
     */
    protected function loadAzPermissions(): Collection
    {
        return $this->getAzPermissions();
    }
}
