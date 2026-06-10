<?php

declare(strict_types=1);

namespace AzGuard;

use AzGuard\Contracts\AzGuardManagerInterface;
use AzGuard\Grants\GrantBuilder;
use AzGuard\Models\DirectGrant;
use AzGuard\Support\Panel;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Override;
use RuntimeException;
use UnitEnum;

final class AzGuardManager implements AzGuardManagerInterface
{
    /** @var array<string, Panel> */
    protected array $panels = [];

    protected ?Panel $currentPanel = null;

    // ─── Panels ──────────────────────────────────────────────────────────────

    #[Override]
    public function registerPanel(Panel|callable $panel): void
    {
        $panelInstance = $panel instanceof Panel ? $panel : $panel();
        $this->panels[$panelInstance->getId()] = $panelInstance;
    }

    /**
     * @return array<string, Panel>
     */
    #[Override]
    public function getPanels(): array
    {
        return $this->panels;
    }

    #[Override]
    public function panel(string $id): ?Panel
    {
        return $this->panels[$id] ?? null;
    }

    #[Override]
    public function currentPanel(): ?Panel
    {
        return $this->currentPanel;
    }

    #[Override]
    public function setCurrentPanel(?Panel $panel): void
    {
        $this->currentPanel = $panel;
    }

    #[Override]
    public function permission(string $panelId, string|UnitEnum $permission): string
    {
        $panel = $this->panel(id: $panelId);

        if (! $panel instanceof Panel) {
            throw new RuntimeException("AzGuard panel [{$panelId}] is not registered.");
        }

        return $panel->resolvePermission(permission: $permission);
    }

    #[Override]
    public function tryPermission(string $panelId, string|UnitEnum $permission): ?string
    {
        $panel = $this->panel(id: $panelId);

        return $panel?->resolvePermission(permission: $permission);
    }

    // ─── Grants API ─────────────────────────────────────────────────────────

    /**
     * Return a fluent GrantBuilder for a user.
     *
     * Example:
     *   AzGuard::forUser($user)->on('app')->ttl(3600)->give('app.x.view');
     */
    #[Override]
    public function forUser(Authenticatable $user): GrantBuilder
    {
        return new GrantBuilder(user: $user);
    }

    /**
     * Shorthand: issue a direct grant.
     *
     * @param  int|null  $ttl  TTL in seconds. null = permanent.
     */
    #[Override]
    public function grantDirect(
        Authenticatable $user,
        string $permissionKey,
        string $panelId = 'app',
        ?int $ttl = null,
    ): DirectGrant {
        return $this->forUser($user)->on($panelId)->ttl($ttl)->give($permissionKey);
    }

    /**
     * Shorthand: revoke a direct grant.
     *
     * @return int Number of deleted records.
     */
    #[Override]
    public function revokeDirect(
        Authenticatable $user,
        string $permissionKey,
        string $panelId = 'app',
    ): int {
        return $this->forUser($user)->on($panelId)->revoke($permissionKey);
    }

    /**
     * Shorthand: list active direct grants for a user in a panel.
     *
     * @return Collection<int, DirectGrant>
     */
    #[Override]
    public function activeGrants(
        Authenticatable $user,
        string $panelId = 'app',
    ): Collection {
        return $this->forUser($user)->on($panelId)->list();
    }
}
