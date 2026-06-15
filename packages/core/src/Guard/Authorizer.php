<?php

declare(strict_types=1);

namespace AzGuard\Guard;

use AzGuard\Contracts\AzGuardManagerInterface;
use AzGuard\Registry\Resolver\EffectivePermissionResolver;
use AzGuard\Support\Config;
use AzGuard\Support\Panel;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Core authorization component.
 *
 * Registered via Gate::before() and:
 * 1) Delegates permission resolution to EffectivePermissionResolver.
 * 2) Returns true for superadmin (wildcard '*').
 * 3) Checks the specific $ability via PermissionSet::grants() (exact + wildcard).
 * 4) Returns null (pass-through) if the user does not implement Authenticatable.
 *
 * Panel resolution order: the current request panel (SetCurrentPanel
 * middleware), else az-guard.default_panel, else the sole registered panel.
 * With no active panel AND more than one registered panel it returns null
 * (deny pass-through) rather than evaluate against an arbitrary panel.
 */
final readonly class Authorizer
{
    public function __construct(
        private EffectivePermissionResolver $resolver,
        private AzGuardManagerInterface $manager,
    ) {}

    public function check(Authorizable $user, string $ability): ?bool
    {
        if (! $user instanceof Authenticatable) {
            return null;
        }

        $panelId = $this->resolvePanelId();

        if ($panelId === null) {
            return null;
        }

        $set = $this->resolver->forUser($user, $panelId);

        if ($set->grants($ability)) {
            return true;
        }

        return null;
    }

    private function resolvePanelId(): ?string
    {
        $current = $this->manager->currentPanel();

        if ($current instanceof Panel) {
            return $current->getId();
        }

        $panels = $this->manager->getPanels();

        // Explicit default wins when it is actually registered.
        $default = Config::defaultPanel();

        if ($default !== null && isset($panels[$default])) {
            return $default;
        }

        // A single registered panel is unambiguous; with several, refuse to
        // guess — returning null lets the Gate deny instead of evaluating the
        // ability against an arbitrary panel.
        return count($panels) === 1 ? array_key_first($panels) : null;
    }
}
