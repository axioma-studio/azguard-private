<?php

declare(strict_types=1);

namespace AzGuard\Filament\Resources\RoleResource\Pages;

use AzGuard\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;
}
