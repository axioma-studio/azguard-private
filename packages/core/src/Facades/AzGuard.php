<?php

declare(strict_types=1);

namespace AzGuard\Facades;

use AzGuard\AzGuardManager;
use AzGuard\Grants\GrantBuilder;
use AzGuard\Models\DirectGrant;
use AzGuard\Support\Panel;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Facade;
use Override;
use UnitEnum;

/**
 * @method static void registerPanel(Closure $panel)
 * @method static array<string, Panel> getPanels()
 * @method static Panel|null panel(string $id)
 * @method static Panel|null currentPanel()
 * @method static void setCurrentPanel(?Panel $panel)
 * @method static string permission(string $panelId, (string | UnitEnum) $permission)
 *
 * --- Grants API (Phase 5–8) ---
 * @method static GrantBuilder forUser(Authenticatable $user)
 * @method static DirectGrant grantDirect(Authenticatable $user, string $permissionKey, string $panelId = 'app', ?int $ttl = null)
 * @method static int revokeDirect(Authenticatable $user, string $permissionKey, string $panelId = 'app')
 * @method static Collection<int, DirectGrant> activeGrants(Authenticatable $user, string $panelId = 'app')
 *
 * @see AzGuardManager
 */
final class AzGuard extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return AzGuardManager::class;
    }
}
