<?php

declare(strict_types=1);

namespace AzGuard\Filament\Concerns;

use AzGuard\Facades\AzGuard;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

trait AuthorizesGuardResource
{
    abstract protected static function guardPanel(): string;

    abstract protected static function viewPermission(): UnitEnum;

    public static function canViewAny(): bool
    {
        return Gate::allows(
            ability: AzGuard::permission(
                panelId: static::guardPanel(),
                permission: static::viewPermission(),
            ),
        );
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
