<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

use AzGuard\Events\RoleAttached;
use AzGuard\Events\RoleDetached;
use AzGuard\Models\Role;
use AzGuard\Registry\Resolver\EffectivePermissionResolver;
use AzGuard\Registry\Values\PermissionSet;
use AzGuard\Support\AzGuardContextBridge;
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
     * Опциональный третий аргумент $context позволяет сделать одноразовую
     * контекстную проверку без изменения глобального состояния.
     * $context — объект с полями contextType, contextId (и опционально panelId).
     * Удобнее использовать hasAzPermissionIn() как alias.
     *
     * Если пакет azguard/context не установлен или $context не передан —
     * поведение идентично предыдущей версии (100% обратная совместимость).
     *
     * Бросает исключение если ключ не зарегистрирован в каталоге
     * (только в strict-режиме; по умолчанию — тихо false).
     *
     * @param  object{contextType: string, contextId: int|string}|null  $context
     */
    public function hasAzPermission(string $permission, string $panelId = 'app', ?object $context = null): bool
    {
        if ($context !== null) {
            return AzGuardContextBridge::checkWithContext($this, $permission, $panelId, $context);
        }

        return $this->getAzPermissionSet($panelId)->grants($permission);
    }

    /**
     * Удобный alias для контекстной проверки:
     *
     *   $user->hasAzPermissionIn('workspace', 42, 'app.posts.edit');
     *   $user->hasAzPermissionIn('workspace', 42, 'app.posts.edit', 'admin');
     *
     * Полностью идемпотентен: не изменяет глобальный контекст.
     * Возвращает false если пакет azguard/context не установлен.
     */
    public function hasAzPermissionIn(
        string $contextType,
        int|string $contextId,
        string $permission,
        string $panelId = 'app',
    ): bool {
        return AzGuardContextBridge::checkInContext(
            user: $this,
            contextType: $contextType,
            contextId: $contextId,
            permission: $permission,
            panelId: $panelId,
        );
    }

    /**
     * Тихая версия: никогда не бросает исключений.
     * Используйте в Blade-условиях и UI.
     *
     * @param  object{contextType: string, contextId: int|string}|null  $context
     */
    public function checkAzPermission(string $permission, string $panelId = 'app', ?object $context = null): bool
    {
        try {
            return $this->hasAzPermission($permission, $panelId, $context);
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
