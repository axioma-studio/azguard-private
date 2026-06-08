<?php

declare(strict_types=1);

namespace AzGuard\Models;

use AzGuard\Support\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Role extends Model
{
    protected $fillable = ['name', 'level', 'class_name'];

    public function getTable(): string
    {
        return Config::rolesTable();
    }

    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            config('auth.providers.users.model'),
            'model',
            Config::modelHasRolesTable()
        );
    }

    /**
     * Permissions assigned to the role via DB (not via PHP class).
     * Used by DatabaseRoleGrantSource.
     */
    public function dbPermissions(): HasMany
    {
        return $this->hasMany(
            RolePermission::class,
            'role_id',
        );
    }

    /**
     * Instantiate the role logic class via the service container.
     * Allows constructor injection in role classes.
     */
    public function getRoleLogic(): ?object
    {
        if (! is_string($this->class_name) || ! class_exists($this->class_name)) {
            return null;
        }

        return app($this->class_name);
    }

    /**
     * Check whether the role has a DB permission for the given panel.
     */
    public function hasDbPermission(string $permissionKey, string $panelId): bool
    {
        return $this->dbPermissions()
            ->where('permission_key', $permissionKey)
            ->where('panel_id', $panelId)
            ->exists();
    }

    /**
     * Find a role by its name.
     */
    public static function findByName(string $name): ?static
    {
        return static::query()->where('name', $name)->first();
    }
}
