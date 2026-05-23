<?php

declare(strict_types=1);

namespace AzGuard\Support;

final class PermissionName
{
    public static function for(Panel $panel, string|\UnitEnum $ability): string
    {
        return $panel->resolvePermission(permission: $ability);
    }
}
