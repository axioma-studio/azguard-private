<?php

declare(strict_types=1);

namespace AzGuard\Guard;

use AzGuard\Contracts\AzGuardManagerInterface;
use AzGuard\Registry\Resolver\EffectivePermissionResolver;
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
 * Panel is resolved from the current request (SetCurrentPanel middleware);
 * falls back to the first registered panel.
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

        return $panels === [] ? null : array_key_first($panels);
    }
}
