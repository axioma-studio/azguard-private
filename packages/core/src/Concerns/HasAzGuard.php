<?php

namespace AzGuard\Concerns;

use Illuminate\Support\Collection;

trait HasAzGuard
{
    /** @var \Illuminate\Support\Collection<int, string>|null */
    private ?Collection $azPermissionsCache = null;

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
        $permissions = $this->getAzPermissions();

        if ($permissions->contains('*')) {
            return true;
        }

        return $permissions->contains($permission);
    }

    /*
    * @return \Illuminate\Support\Collection
    */
    public function getAzPermissions(): \Illuminate\Support\Collection
    {
        if ($this->azPermissionsCache !== null) {
            return $this->azPermissionsCache;
        }

        $this->azPermissionsCache = $this->roles
            ->map(fn ($roleModel) => $roleModel->getRoleLogic())
            ->filter()
            ->flatMap(function ($roleLogic) {
                $permissions = $roleLogic->permissions();

                return is_array($permissions) ? $permissions : [];
            })
            ->unique()
            ->values();

        return $this->azPermissionsCache;
    }
}
