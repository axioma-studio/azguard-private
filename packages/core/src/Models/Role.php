<?php

namespace AzGuard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Role extends Model
{
    protected $fillable = ['name', 'level', 'class_name'];

    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            config('auth.providers.users.model'),
            'model',
            config('az-guard.table_names.model_has_roles')
        );
    }

    /**
     * Пермиссии, назначенные роли через БД (не через PHP-класс).
     * Используется DatabaseRoleGrantSource.
     */
    public function dbPermissions(): HasMany
    {
        return $this->hasMany(
            RolePermission::class,
            'role_id',
        );
    }

    /**
     * Инстанцирует класс логики роли (например, SuperAdminRole).
     */
    public function getRoleLogic(): ?object
    {
        if (! is_string($this->class_name) || ! class_exists($this->class_name)) {
            return null;
        }

        return new $this->class_name;
    }

    /**
     * Проверить, есть ли DB-пермиссия для данной панели.
     */
    public function hasDbPermission(string $permissionKey, string $panelId): bool
    {
        return $this->dbPermissions()
            ->where('permission_key', $permissionKey)
            ->where('panel_id', $panelId)
            ->exists();
    }
}
