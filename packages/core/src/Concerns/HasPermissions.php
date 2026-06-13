<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

use AzGuard\AzGuardManager;
use AzGuard\Contracts\ContextGuard;
use AzGuard\Contracts\PermissionContext;
use AzGuard\Contracts\PermissionResolverInterface;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Support\Collection;
use Throwable;

trait HasPermissions
{
    /**
     * Check if the user has a permission on a panel.
     *
     * Optional $context allows a one-off contextual check without changing
     * global state. Use hasPermissionIn() as a more readable alternative.
     */
    public function hasPermission(string $permission, string $panelId = 'app', ?PermissionContext $context = null): bool
    {
        if ($context instanceof PermissionContext) {
            $guard = $this->contextGuard();

            // No context package installed — fall back to a global check.
            return $guard === null
                ? $this->permissionSet($panelId)->grants($permission)
                : $guard->checkInContext($this, $context->contextType(), $context->contextId(), $permission, $panelId);
        }

        return $this->permissionSet($panelId)->grants($permission);
    }

    /**
     * Contextual permission check — does not mutate global state.
     *
     *   $user->hasPermissionIn('workspace', 42, 'app.posts.edit');
     *   $user->hasPermissionIn('workspace', 42, 'app.posts.edit', 'admin');
     */
    public function hasPermissionIn(
        string $contextType,
        int|string $contextId,
        string $permission,
        string $panelId = 'app',
    ): bool {
        return $this->contextGuard()?->checkInContext(
            $this,
            $contextType,
            $contextId,
            $permission,
            $panelId,
        ) ?? false;
    }

    /**
     * Resolve the optional context package's ContextGuard, or null when the
     * azguard/context package is not installed.
     */
    private function contextGuard(): ?ContextGuard
    {
        return app()->bound(ContextGuard::class)
            ? app(ContextGuard::class)
            : null;
    }

    /**
     * Silent version: never throws. Use in Blade / UI.
     */
    public function checkPermission(string $permission, string $panelId = 'app', ?PermissionContext $context = null): bool
    {
        try {
            return $this->hasPermission($permission, $panelId, $context);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Get the PermissionSet for a panel.
     * All caching is delegated to EffectivePermissionResolver.
     */
    public function permissionSet(string $panelId = 'app'): PermissionSet
    {
        return $this->permissionResolver()->forUser($this, $panelId);
    }

    /**
     * Get all permission keys for a panel as a Collection.
     *
     * @return Collection<int, string>
     */
    public function permissions(string $panelId = 'app'): Collection
    {
        return collect($this->permissionSet($panelId)->keys());
    }

    /**
     * Flush the permission cache for this user.
     * If $panelId is null, flushes all panels.
     * Called automatically by assignRole / removeRole / syncRoles.
     */
    public function flushPermissions(?string $panelId = null): void
    {
        $resolver = $this->permissionResolver();

        if ($panelId !== null) {
            $resolver->forgetForUser($this, $panelId);

            return;
        }

        $panels = app(AzGuardManager::class)->getPanels();

        foreach (array_keys($panels) as $id) {
            $resolver->forgetForUser($this, $id);
        }

        if (! isset($panels['app'])) {
            $resolver->forgetForUser($this, 'app');
        }
    }

    private function permissionResolver(): PermissionResolverInterface
    {
        return app(PermissionResolverInterface::class);
    }
}
