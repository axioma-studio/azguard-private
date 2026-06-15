<?php

declare(strict_types=1);

namespace AzGuard\Support;

use AzGuard\AzGuardManager;
use BackedEnum;
use UnitEnum;

/**
 * Resolves a permission argument (string or enum) to its stored catalog key.
 *
 * A string is treated as an already-resolved full key and returned unchanged.
 * An enum is scoped to the panel exactly like the catalog
 * ({@see Panel::resolvePermission} via the registered panel), so an enum case
 * `DocumentsPermission::View = 'documents.view'` resolves to "app.documents.view".
 * When the panel is not registered, the enum falls back to its raw value/name.
 */
final class PermissionName
{
    public static function resolve(string|UnitEnum $permission, string $panelId): string
    {
        if (is_string($permission)) {
            return $permission;
        }

        $resolved = app(AzGuardManager::class)->tryPermission($panelId, $permission);

        return $resolved ?? ($permission instanceof BackedEnum ? (string) $permission->value : $permission->name);
    }
}
