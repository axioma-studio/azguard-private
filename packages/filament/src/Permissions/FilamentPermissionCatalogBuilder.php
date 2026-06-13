<?php

declare(strict_types=1);

namespace AzGuard\Filament\Permissions;

use AzGuard\Registry\Contracts\PermissionCatalogBuilder;
use Override;

/**
 * Feeds the AzGuard permission catalog from the Filament panel's resources and
 * pages — the "database" source. Discovered keys become known permissions, so
 * they appear in the Role UI and roles can be granted them without any code in
 * the Filament resources themselves.
 */
final readonly class FilamentPermissionCatalogBuilder implements PermissionCatalogBuilder
{
    public function __construct(
        private string $panelId,
        private PermissionSchema $schema,
        private PermissionDiscovery $discovery,
    ) {}

    #[Override]
    public function build(string $panelId): array
    {
        if ($panelId !== $this->panelId) {
            return [];
        }

        return $this->schema->definitions($panelId, $this->discovery->subjects($panelId));
    }

    #[Override]
    public function supports(string $panelId): bool
    {
        return $panelId === $this->panelId;
    }
}
