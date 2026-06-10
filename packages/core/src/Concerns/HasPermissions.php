<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

use AzGuard\AzGuardManager;
use AzGuard\Contracts\ContextContract;
use AzGuard\Registry\Resolver\EffectivePermissionResolver;
use AzGuard\Registry\Values\PermissionSet;
use AzGuard\Support\AzGuardContextBridge;
use AzGuard\Support\PermissionContext;
use Illuminate\Support\Collection;
use Throwable;

trait HasPermissions
{
    /**
     * Check if the user has a permission on a panel.
     *
     * The optional $context parameter allows a one-off contextual check without
     * changing global state. Use {@see hasPermissionIn()} as a more readable
     * alternative when you have a contextType + contextId at hand.
     *
     * Pass a {@see PermissionContext} DTO (preferred) or any object that
     * implements {@see ContextContract}.
     *
     * @param  ContextContract|null  $context
     */
    public function hasPermission(
        string $permission,
        string $panelId = 'app',
        ?ContextContract $context = null,
    ): bool {
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
     * @param  ContextContract|null  $context
     */
    public function checkPermission(
        string $permission,
        string $panelId = 'app',
        ?ContextContract $context = null,
    ): bool {
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
        return collect($this->permissionSet($panelId)->keys());
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

        // Safety: always flush 'app' even if not registered as a named panel.
        if (! isset($panels['app'])) {
            $resolver->forgetForUser($this, 'app');
        }
    }
}
