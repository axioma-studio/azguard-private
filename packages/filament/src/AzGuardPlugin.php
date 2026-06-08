<?php

declare(strict_types=1);

namespace AzGuard\Filament;

use AzGuard\Filament\Resources\DirectGrantResource;
use AzGuard\Filament\Resources\RoleResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

/**
 * Filament v3 plugin.
 *
 * Регистрация в панели:
 *
 * $panel->plugins([
 *     AzGuardPlugin::make()->forPanel('admin'),
 * ])
 */
final class AzGuardPlugin implements Plugin
{
    private string $panelId = 'app';

    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'az-guard';
    }

    /**
     * Указать, для какой AzGuard-панели отображать UI управления.
     * Позволяет фильтровать права и роли только данной панели.
     */
    public function forPanel(string $panelId): static
    {
        $this->panelId = $panelId;

        return $this;
    }

    public function getPanelId(): string
    {
        return $this->panelId;
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            RoleResource::class,
            DirectGrantResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
