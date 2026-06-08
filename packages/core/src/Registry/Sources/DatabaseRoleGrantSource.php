<?php

declare(strict_types=1);

namespace AzGuard\Registry\Sources;

use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

/**
 * Источник grants из DB-ролей через таблицу az_guard_role_permissions.
 *
 * Покрывает роли без class_name (чистые DB-роли, не PHP-классы).
 * Приоритет 90 (ClassRoleGrantSource = 100, DirectGrantSource = 80).
 *
 * Для производительности использует один JOIN вместо N+1.
 */
final class DatabaseRoleGrantSource implements GrantSource
{
    public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet
    {
        $userId = $user->getAuthIdentifier();
        $userClass = get_class($user);

        $rolesTable = config('az-guard.table_names.roles', 'az_guard_roles');
        $pivotTable = config('az-guard.table_names.model_has_roles', 'az_guard_model_has_roles');
        $permTable  = config('az-guard.table_names.role_permissions', 'az_guard_role_permissions');

        /**
         * SELECT rp.permission_key
         * FROM   az_guard_role_permissions rp
         * JOIN   az_guard_model_has_roles  mhr ON mhr.role_id = rp.role_id
         * WHERE  mhr.model_type = ?
         * AND    mhr.model_id   = ?
         * AND    rp.panel_id    = ?
         */
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
