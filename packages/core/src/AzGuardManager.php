<?php

namespace AzGuard;

use AzGuard\Support\Panel;
use Closure;

class AzGuardManager
{
    /** @var array<string, Panel> */
    protected array $panels = [];

    public function registerPanel(Closure $panel): void
    {
        $panelInstance = $panel();
        $this->panels[$panelInstance->getId()] = $panelInstance;
    }

    public function getPanels(): array
    {
        return $this->panels;
    }

    public function panel(string $id): ?Panel
    {
        return $this->panels[$id] ?? null;
    }
}
