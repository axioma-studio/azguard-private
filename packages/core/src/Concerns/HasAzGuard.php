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

    /**
     * Собирает все разрешения из всех ролей пользователя.
     */
    public function getAzPermissions(): Collection
    {
        // Используем кэширование в рамках одного запроса, чтобы не пересобирать массив
        return once(function () {
            return $this->roles->flatMap(function ($roleModel) {
                // $roleModel — это модель из БД (AzRole). 
                // Нам нужно получить объект логики (например, SuperAdminRole)
                $roleLogic = $roleModel->getRoleLogic();

                return $roleLogic ? $roleLogic->permissions() : [];
            })->unique()->values();
        });
    }
}
