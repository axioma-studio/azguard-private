<?php

declare(strict_types=1);

namespace AzGuard\Filament\Resources;

use AzGuard\Filament\Concerns\AuthorizesAzGuardPermissions;
use Filament\Resources\Resource;
use Override;

abstract class AzGuardResource extends Resource
{
    use AuthorizesAzGuardPermissions;

    protected static ?string $viewAnyPermission = null;

    #[Override]
    public static function canViewAny(): bool
    {
        $permission = static::$viewAnyPermission;

        if ($permission === null) {
            return parent::canViewAny();
        }

        $user = auth()->user();

        if ($user === null || ! method_exists($user, 'hasAzPermission')) {
            return false;
        }

        return $user->hasAzPermission(permission: $permission);
    }
}
