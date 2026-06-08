<?php

declare(strict_types=1);

namespace AzGuard\Registry\Sources;

use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

/**
 * Grant source from DB roles via az_guard_role_permissions.
 *
 * Covers roles without class_name (pure DB roles, not PHP classes).
 * Priority 90 (ClassRoleGrantSource = 100, DirectGrantSource = 80).
 *
 * Uses a single JOIN instead of N+1 queries for performance.
 */
final class DatabaseRoleGrantSource implements GrantSource
{
    public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet
    {
        $userId    = $user->getAuthIdentifier();
        $userClass = $user::class;

        $rolesTable = config('az-guard.table_names.roles', 'az_guard_roles');
        $pivotTable = config('az-guard.table_names.model_has_roles', 'az_guard_model_has_roles');
        $permTable  = config('az-guard.table_names.role_permissions', 'az_guard_role_permissions');

        $keys = DB::table($permTable)
            ->join($pivotTable, "{$pivotTable}.role_id", '=', "{$permTable}.role_id")
            ->where("{$pivotTable}.model_type", $userClass)
            ->where("{$pivotTable}.model_id", $userId)
            ->where("{$permTable}.panel_id", $panelId)
            ->pluck("{$permTable}.permission_key")
            ->all();

        if ($keys === []) {
            return PermissionSet::empty();
        }

        if (in_array('*', $keys, strict: true)) {
            return PermissionSet::wildcard();
        }

        return PermissionSet::fromKeys($keys);
    }

    public function priority(): int
    {
        return 90;
    }
}
