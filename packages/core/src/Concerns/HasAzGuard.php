<?php

namespace AzGuard\Concerns;

use Illuminate\Support\Collection;

trait HasAzGuard
{
    // Твои существующие связи
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
     * Проверяет, дает ли хоть одна роль пользователя указанное разрешение.
     */
    public function hasAzPermission(string $permission): bool
    {
        return $this->getAzPermissions()->contains($permission);
    }

    /*
    * @return \Illuminate\Support\Collection
    */
    public function getAzPermissions(): \Illuminate\Support\Collection
    {
        // Используем helper once() (доступен в Laravel 11+) или локальный кэш,
        // чтобы не выполнять тяжелую логику рефлексии и коллекций при каждой проверке @can
        return once(function () {
            return $this->roles
                ->map(function ($roleModel) {
                    // Вызываем метод, который мы добавили в модель Role
                    return $roleModel->getRoleLogic();
                })
                ->filter() // Удаляем null, если класс роли не найден
                ->flatMap(function ($roleLogic) {
                    // Вызываем метод permissions() из PHP-класса (например, SuperAdminRole)
                    $permissions = $roleLogic->permissions();

                    return is_array($permissions) ? $permissions : [];
                })
                ->unique()
                ->values();
        });
    }
}
