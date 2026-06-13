<?php

declare(strict_types=1);

namespace AzGuard\Filament;

use AzGuard\Filament\Pages\DoctorPage;
use AzGuard\Filament\Resources\DirectGrantResource;
use AzGuard\Filament\Resources\RoleResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Resources\Resource;
use Override;

/**
 * AzGuard Filament plugin (Filament 5).
 *
 * Register in your PanelProvider:
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
        return new self;
    }

    #[Override]
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

    #[Override]
    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                RoleResource::class,
                DirectGrantResource::class,
            ])
            ->pages([
                DoctorPage::class,
            ]);
    }

    #[Override]
    public function boot(Panel $panel): void
    {
        if (! config('az-guard-filament.enforce', true)) {
            return;
        }

        // Force Filament to consult the Gate for every resource (instead of
        // allowing when no policy exists) so AzGuard's ResourceGate enforces
        // the generated permissions — no per-resource code required.
        foreach ($panel->getResources() as $resource) {
            if (is_subclass_of($resource, Resource::class)) {
                $resource::checkPolicyExistence(false);
            }
        }
    }
}
