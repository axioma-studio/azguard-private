<?php

declare(strict_types=1);

namespace AzGuard\Registry\Sources;

use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Values\PermissionSet;
use AzGuard\Support\Config;
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

        $pivotTable = Config::modelHasRolesTable();
        $permTable  = Config::tableName('role_permissions');

        $keys = DB::table($permTable)
            ->join($pivotTable, "{$pivotTable}.role_id", '=', "{$permTable}.role_id")
            ->where("{$pivotTable}.model_type", $userClass)
            ->where("{$pivotTable}.model_id", $userId)
            ->where("{$permTable}.panel_id", $panelId)
            ->pluck("{$permTable}.permission_key")
            ->all();

        return PermissionSet::fromRawKeys($keys);
    }

    public function priority(): int
    {
        return 90;
    }
}
