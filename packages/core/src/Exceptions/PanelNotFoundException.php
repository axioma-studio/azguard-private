<?php

declare(strict_types=1);

namespace AzGuard\Exceptions;

/**
 * Thrown when an AzGuard panel ID is not registered.
 */
final class PanelNotFoundException extends AzGuardException
{
    public static function forId(string $panelId): self
    {
        return new self("AzGuard panel [{$panelId}] is not registered.");
    }
}
