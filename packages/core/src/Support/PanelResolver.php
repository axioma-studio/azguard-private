<?php

declare(strict_types=1);

namespace AzGuard\Support;

use AzGuard\Exceptions\PanelNotSetException;
use AzGuard\Facades\AzGuard;
use BackedEnum;
use Illuminate\Support\Facades\Log;

/**
 * Centralises the recurring pattern:
 *   $panelId ?? AzGuard::currentPanel()?->getId()
 *
 * Usage:
 *   PanelResolver::resolve($panelId)          // returns string|null
 *   PanelResolver::resolveOrFail($panelId)    // throws PanelNotSetException
 *   PanelResolver::resolveDefault($panelId)   // explicit ?? config default ?? 'app'
 */
final class PanelResolver
{
    /**
     * Default panel for the model permission APIs ($user->hasPermission(), …).
     *
     * The explicit panel wins; otherwise az-guard.default_panel, otherwise the
     * built-in 'app' fallback. The single place the 'app' literal lives — set
     * az-guard.default_panel to change it project-wide. Does not consult the
     * current request panel (that is the Authorizer's job).
     */
    public static function resolveDefault(?string $panelId): string
    {
        if ($panelId !== null) {
            self::warnIfUnregistered($panelId);

            return $panelId;
        }

        return Config::defaultPanel() ?? 'app';
    }

    /**
     * Debug-only, fail-soft: flag an explicit panel id that is not registered
     * (a permission check against it silently resolves to an empty catalog).
     * Only runs under app.debug and once panels exist, so headless/test
     * bootstraps stay quiet. Never throws — resolution is best-effort by design.
     */
    private static function warnIfUnregistered(string $panelId): void
    {
        if (! config('app.debug')) {
            return;
        }

        $panels = AzGuard::getPanels();

        if ($panels !== [] && ! isset($panels[$panelId])) {
            Log::debug("AzGuard: permission check against unregistered panel [{$panelId}].", [
                'registered' => array_keys($panels),
            ]);
        }
    }

    /**
     * Normalise a panel identifier to its string id. Accepts a backed enum
     * (e.g. PanelId::Admin) or a plain string, so the whole panel API can be
     * called type-safely with enums.
     */
    public static function normalizeId(string|BackedEnum $panelId): string
    {
        return $panelId instanceof BackedEnum ? (string) $panelId->value : $panelId;
    }

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
            ?? throw new PanelNotSetException;
    }
}
