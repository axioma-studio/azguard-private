<?php

declare(strict_types=1);

namespace AzGuard\Filament\Resources\DirectGrantResource\Pages;

use AzGuard\Filament\Resources\DirectGrantResource;
use Filament\Resources\Pages\ListRecords;

final class ListDirectGrants extends ListRecords
{
    protected static string $resource = DirectGrantResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
