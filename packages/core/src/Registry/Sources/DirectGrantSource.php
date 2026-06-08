<?php

declare(strict_types=1);

namespace AzGuard\Registry\Sources;

use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

/**
 * Источник grants из таблицы az_guard_direct_grants.
 *
 * Прямые пермиссии пользователю без роли: временные доступы, overrides, временное расширение прав.
 * Фильтрует по expires_at: null = бессрочно, иначе только активные.
 * Приоритет 80.
 */
final class DirectGrantSource implements GrantSource
{
    public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet
    {
        $userId = $user->getAuthIdentifier();
        $userClass = get_class($user);

        $table = config('az-guard.table_names.direct_grants', 'az_guard_direct_grants');

        $keys = DB::table($table)
            ->where('model_type', $userClass)
            ->where('model_id', $userId)
            ->where('panel_id', $panelId)
            ->where(function ($q): void {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->pluck('permission_key')
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
        return 80;
    }
}
