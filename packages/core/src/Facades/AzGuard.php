<?php

declare(strict_types=1);

namespace AzGuard\Facades;

use AzGuard\AzGuardManager;
use AzGuard\Support\Panel;
use Closure;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void registerPanel(Closure $panel)
 * @method static array<string, Panel> getPanels()
 * @method static Panel|null panel(string $id)
 * @method static Panel|null currentPanel()
 * @method static void setCurrentPanel(?Panel $panel)
 * @method static string permission(string $panelId, string|\UnitEnum $permission)
 *
 * @see AzGuardManager
 */
final class AzGuard extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AzGuardManager::class;
    }
}
