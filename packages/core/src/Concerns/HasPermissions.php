<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

use AzGuard\AzGuardManager;
use AzGuard\Registry\Resolver\EffectivePermissionResolver;
use AzGuard\Registry\Values\PermissionSet;
use AzGuard\Support\AzGuardContextBridge;
use Illuminate\Support\Collection;
use Throwable;

trait HasPermissions
{
    /**
     * Check if the user has a permission on a panel.
     *
     * Optional $context allows a one-off contextual check without changing
     * global state. Use hasPermissionIn() as a more readable alternative.
     *
     * @param  object{contextType: string, contextId: int|string}|null  $context
     */
    public function hasPermission(string $permission, string $panelId = 'app', ?object $context = null): bool
    {
        if ($context !== null) {
            return AzGuardContextBridge::checkWithContext($this, $permission, $panelId, $context);
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
        return AzGuardContextBridge::checkInContext(
            user: $this,
            contextType: $contextType,
            contextId: $contextId,
            permission: $permission,
            panelId: $panelId,
        );
    }

    /**
     * Silent version: never throws. Use in Blade / UI.
     *
     * @param  object{contextType: string, contextId: int|string}|null  $context
     */
    public function checkPermission(string $permission, string $panelId = 'app', ?object $context = null): bool
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
        return app(EffectivePermissionResolver::class)->forUser($this, $panelId);
    }

    /**
     * Get all permission keys for a panel as a Collection.
     *
     * @return Collection<int, string>
     */
    public function permissions(string $panelId = 'app'): Collection
    {
        return collect($this->permissionSet($panelId)->toArray());
    }

    /**
     * Flush the permission cache for this user.
     * If $panelId is null, flushes all panels.
     * Called automatically by assignRole / removeRole / syncRoles.
     */
    public function flushPermissions(?string $panelId = null): void
    {
        $resolver = app(EffectivePermissionResolver::class);

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
}
