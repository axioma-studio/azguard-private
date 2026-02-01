<?php

namespace AzGuard\Facades;

use Illuminate\Support\Facades\Facade;
use AzGuard\AzGuardManager;

/**
 * @method static void registerPanel(\Closure $panel)
 * @method static array getPanels()
 * @method static \AzGuard\Support\Panel|null panel(string $id)
 * * @see \AzGuard\AzGuardManager
 */
class AzGuard extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AzGuardManager::class;
    }
}
