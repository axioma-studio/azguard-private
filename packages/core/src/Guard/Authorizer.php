<?php

declare(strict_types=1);

namespace AzGuard\Guard;

use AzGuard\Registry\Resolver\EffectivePermissionResolver;
use AzGuard\Support\Config;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Core authorization component.
 *
 * Registered via Gate::before() and:
 * 1) Delegates permission resolution to EffectivePermissionResolver.
 * 2) Returns true for superadmin (wildcard '*').
 * 3) Checks the specific $ability via PermissionSet.
 * 4) Returns null (pass-through) if the user does not use HasAzGuard.
 *
 * Panel is resolved from the current request (SetCurrentPanel middleware);
 * falls back to the first registered panel.
 */
final class Authorizer
{
    public function __construct(
        private readonly EffectivePermissionResolver $resolver,
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

        if ($set->isWildcard()) {
            return true;
        }

        if (Config::wildcardEnabled()) {
            foreach ($set->keys() as $permission) {
                if ($this->matchesWildcard($permission, $ability)) {
                    return true;
                }
            }
        }

        if ($set->has($ability)) {
            return true;
        }

        return null;
    }

    private function resolvePanelId(): ?string
    {
        $manager = app(\AzGuard\AzGuardManager::class);

        $current = $manager->currentPanel();

        if ($current !== null) {
            return $current->getId();
        }

        $panels = $manager->getPanels();

        return $panels === [] ? null : array_key_first($panels);
    }

    /**
     * Wildcard match: 'admin.*' matches 'admin.users.view'.
     */
    private function matchesWildcard(string $pattern, string $ability): bool
    {
        if (! str_contains($pattern, '*')) {
            return false;
        }

        $regex = '/^' . str_replace(['.', '*'], ['\\.', '.*'], $pattern) . '$/';

        return (bool) preg_match($regex, $ability);
    }
}
