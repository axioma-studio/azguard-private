<?php

declare(strict_types=1);

namespace AzGuard\Filament\Resources\RoleResource\Pages;

use AzGuard\Filament\Resources\RoleResource;
use AzGuard\Filament\Resources\RoleResource\RelationManagers\RolePermissionsRelationManager;
use AzGuard\Filament\Resources\RoleResource\RelationManagers\RoleUsersRelationManager;
use Filament\Resources\Pages\EditRecord;
use Override;

final class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    #[Override]
    public function getRelationManagers(): array
    {
        return [
            RolePermissionsRelationManager::class,
            RoleUsersRelationManager::class,
        ];
    }
}
