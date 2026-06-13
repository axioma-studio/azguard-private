<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

use AzGuard\Models\Role;
use AzGuard\Support\Config;

/**
 * Shared helper: resolve a Role model from a name string or a Role instance.
 *
 * Used by HasAzGuard and HasScopedRoles to avoid duplicating the same
 * string-to-model lookup logic.
 */
trait ResolvesRole
{
    /**
     * Resolve a Role model from a name string or return the instance directly.
     *
     * Returns null when the role name does not exist in the database.
     */
    protected function resolveRole(string|Role $role): ?Role
    {
        if ($role instanceof Role) {
            return $role;
        }

        /** @var class-string<Role> $roleClass */
        $roleClass = Config::roleModel();

        return $roleClass::findByName($role);
    }
}
