<?php

declare(strict_types=1);

namespace AzGuard;

use AzGuard\Contracts\AzGuardManagerInterface;
use AzGuard\Exceptions\PanelNotFoundException;
use AzGuard\Grants\GrantBuilder;
use AzGuard\Models\DirectGrant;
use AzGuard\Support\Panel;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;

final class AzGuardManager implements AzGuardManagerInterface
{
    /** @var array<string, Panel> */
    protected array $panels = [];

    protected ?Panel $currentPanel = null;

    // ─── Panels ───────────────────────────────────────────────────────────────────

    /**
     * Register a panel via a factory closure or a Panel instance.
     *
     * Accepts both:
     *   AzGuard::registerPanel(fn () => new Panel('app', ...));
     *   AzGuard::registerPanel($panelInstance);
     */
    public function registerPanel(Closure|Panel $panel): void
    {
        $panelInstance = $panel instanceof Panel ? $panel : $panel();
        $this->panels[$panelInstance->getId()] = $panelInstance;
    }

    /** @return array<string, Panel> */
    public function getPanels(): array
    {
        return $this->panels;
    }

    public function panel(string $id): ?Panel
    {
        return $this->panels[$id] ?? null;
    }

    public function currentPanel(): ?Panel
    {
        return $this->currentPanel;
    }

    public function setCurrentPanel(?Panel $panel): void
    {
        $this->currentPanel = $panel;
    }

    /**
     * Resolve a fully-qualified permission key for the given panel.
     *
     * @throws PanelNotFoundException
     */
    public function permission(string $panelId, string|\UnitEnum $permission): string
    {
        $panel = $this->panel(id: $panelId);

        if ($panel === null) {
            throw PanelNotFoundException::forId($panelId);
        }

        return $panel->resolvePermission(permission: $permission);
    }

    // ─── Grants API ─────────────────────────────────────────────────────────────

    /**
     * Return a fluent GrantBuilder for the given user.
     *
     *   AzGuard::forUser($user)->on('app')->ttl(3600)->give('app.x.view');
     */
    public function forUser(Authenticatable $user): GrantBuilder
    {
        return new GrantBuilder(user: $user);
    }

    /**
     * Shorthand: grant a direct permission.
     *
     * @param  int|null  $ttl  TTL in seconds. null = no expiry.
     */
    public function grantDirect(
        Authenticatable $user,
        string $permissionKey,
        string $panelId = 'app',
        ?int $ttl = null,
    ): DirectGrant {
        return $this->forUser($user)->on($panelId)->ttl($ttl)->give($permissionKey);
    }

    /**
     * Shorthand: revoke a direct permission.
     *
     * @return int Number of deleted records.
     */
    public function revokeDirect(
        Authenticatable $user,
        string $permissionKey,
        string $panelId = 'app',
    ): int {
        return $this->forUser($user)->on($panelId)->revoke($permissionKey);
    }

    /**
     * Shorthand: list active grants for user on a panel.
     *
     * @return Collection<int, DirectGrant>
     */
    public function activeGrants(
        Authenticatable $user,
        string $panelId = 'app',
    ): Collection {
        return $this->forUser($user)->on($panelId)->list();
    }
}
