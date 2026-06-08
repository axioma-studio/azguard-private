<?php

declare(strict_types=1);

namespace AzGuard\Registry\Sources;

use AzGuard\Models\DirectGrant;
use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Grant source from the az_guard_direct_grants table.
 *
 * Direct per-user permissions without a role: temporary access,
 * overrides, temporary privilege escalation.
 * Filters by expires_at: null = never expires, otherwise active only.
 * Priority: 80.
 */
final class DirectGrantSource implements GrantSource
{
    public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet
    {
        $keys = DirectGrant::query()
            ->where('grantable_type', $user::class)
            ->where('grantable_id', $user->getAuthIdentifier())
            ->where('panel_id', $panelId)
            ->active()
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
