<?php

declare(strict_types=1);

namespace AzGuard;

use AzGuard\Support\Panel;
use Closure;

final class AzGuardManager
{
    /** @var array<string, Panel> */
    protected array $panels = [];

    protected ?Panel $currentPanel = null;

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
}
