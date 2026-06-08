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

final class AzGuardManager implements AzGuardManagerInterface
{
    /** @var array<string, Panel> */
    protected array $panels = [];

    protected ?Panel $currentPanel = null;

    // -------------------------------------------------------------------------
    // Panel registry
    // -------------------------------------------------------------------------

    public function registerPanel(Closure $panel): void
    {
        $panelInstance = $panel();
        $this->panels[$panelInstance->getId()] = $panelInstance;
    }

    /**
     * @return array<string, Panel>
     */
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

    public function permission(string $panelId, string|\UnitEnum $permission): string
    {
        $panel = $this->panel(id: $panelId);

        if ($panel === null) {
            throw new \RuntimeException("Панель AzGuard [{$panelId}] не зарегистрирована.");
        }

        return $panel->resolvePermission(permission: $permission);
    }

    // -------------------------------------------------------------------------
    // Fluent Grants API (Phase 5)
    // -------------------------------------------------------------------------

    /**
     * Получить fluent builder для управления grants пользователя.
     *
     * @example
     *   AzGuard::forUser($user)
     *       ->on('app')
     *       ->ttl(86400)
     *       ->give('app.documents.export');
     */
    public function forUser(Authenticatable $user): GrantBuilder
    {
        return new GrantBuilder(user: $user);
    }

    /**
     * Выдать direct grant без fluent-цепочки.
     *
     * @example
     *   AzGuard::grantDirect($user, 'app.documents.export', 'app', ttl: 3600);
     */
    public function grantDirect(
        Authenticatable $user,
        string          $permissionKey,
        string          $panelId = 'app',
        ?int            $ttl = null,
    ): DirectGrant {
        return $this->forUser($user)
            ->on($panelId)
            ->ttl($ttl ?? 0)
            ->give($permissionKey);
    }

    /**
     * Отозвать direct grant.
     */
    public function revokeDirect(
        Authenticatable $user,
        string          $permissionKey,
        string          $panelId = 'app',
    ): int {
        return $this->forUser($user)
            ->on($panelId)
            ->revoke($permissionKey);
    }

    /**
     * Вернуть все активные direct grants пользователя для панели.
     *
     * @return Collection<int, DirectGrant>
     */
    public function activeGrants(
        Authenticatable $user,
        string          $panelId = 'app',
    ): Collection {
        return $this->forUser($user)
            ->on($panelId)
            ->list();
    }
}
