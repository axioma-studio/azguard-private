<?php

declare(strict_types=1);

namespace AzGuard\Support;

use AzGuard\Facades\AzGuard;
use AzGuard\Exceptions\PanelNotSetException;

/**
 * Centralises the recurring pattern:
 *   $panelId ?? AzGuard::currentPanel()?->getId()
 *
 * Usage:
 *   PanelResolver::resolve($panelId)          // returns string|null
 *   PanelResolver::resolveOrFail($panelId)    // throws PanelNotSetException
 */
final class PanelResolver
{
    /**
     * Return the explicit panel ID, or fall back to the current AzGuard panel.
     * Returns null when neither is available.
     */
    public static function resolve(?string $panelId): ?string
    {
        return $panelId ?? AzGuard::currentPanel()?->getId();
    }

    /**
     * Same as resolve(), but throws when no panel can be determined.
     *
     * @throws PanelNotSetException
     */
    public static function resolveOrFail(?string $panelId): string
    {
        return $panelId
            ?? AzGuard::currentPanel()?->getId()
            ?? throw new PanelNotSetException();
    }
}
