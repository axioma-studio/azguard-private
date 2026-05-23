<?php

declare(strict_types=1);

namespace AzGuard\Filament\Resources;

use AzGuard\Filament\Concerns\AuthorizesGuardResource;
use Filament\Resources\Resource;

abstract class GuardResource extends Resource
{
    use AuthorizesGuardResource;
}
