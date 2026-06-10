<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

use Illuminate\Support\Collection;

/**
 * Convenience trait that composes HasRoles and HasPermissions.
 * Use this on your User model for the full AzGuard API.
 * You can instead use HasRoles or HasPermissions individually
 * if you only need a subset of the functionality.
 */
trait HasAzGuard
{
    use HasPermissions, HasRoles;

    /**
     * Check whether the user has the given role by name.
     */
    public function hasAzRole(string $name): bool
    {
        return $this->hasRole($name);
    }

    /**
     * Check whether the user has a specific permission on a panel.
     */
    public function hasAzPermission(string $permission, string $panelId = 'app'): bool
    {
        return $this->hasPermission($permission, $panelId);
    }

    /**
     * Contextual permission check — does not mutate global state.
     *
     *   $user->hasAzPermissionIn('workspace', 42, 'app.posts.edit');
     *   $user->hasAzPermissionIn('workspace', 42, 'app.posts.edit', 'admin');
     */
    public function hasAzPermissionIn(
        string $contextType,
        int|string $contextId,
        string $permission,
        string $panelId = 'app',
    ): bool {
        return $this->hasPermissionIn($contextType, $contextId, $permission, $panelId);
    }

    /**
     * Get all active permission keys for a panel.
     *
     * @return Collection<int, string>
     */
    public function getAzPermissions(string $panelId = 'app'): Collection
    {
        return $this->permissions($panelId);
    }

    /**
     * Clear the resolved permission cache for this user.
     * Alias for flushPermissions() with optional panelId.
     */
    public function clearAzPermissionsCache(?string $panelId = null): void
    {
        $this->flushPermissions($panelId);
    }
}
