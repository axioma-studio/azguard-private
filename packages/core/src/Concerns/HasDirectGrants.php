<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

use AzGuard\Models\DirectGrant;
use AzGuard\Support\Config;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Adds direct-grant support to a User model.
 *
 * Direct grants are one-off permission assignments that bypass roles.
 * They are checked by DirectGrantSource inside EffectivePermissionResolver.
 *
 * Usage:
 *   $user->grantDirect('app.posts.edit');
 *   $user->hasDirectGrant('app.posts.edit'); // true
 *   $user->revokeGrant('app.posts.edit');
 */
trait HasDirectGrants
{
    /**
     * All direct grants belonging to this model.
     */
    public function activeGrants(): HasMany
    {
        return $this->hasMany(Config::directGrantModel(), 'model_id')
            ->where('model_type', $this->getMorphClass());
    }

    /**
     * Check whether this model has a specific direct grant.
     */
    public function hasDirectGrant(string $permission): bool
    {
        return $this->activeGrants()
            ->where('permission', $permission)
            ->exists();
    }

    /**
     * Assign a direct grant for the given permission.
     * Silently ignores duplicates via firstOrCreate.
     */
    public function grantDirect(string $permission): static
    {
        DirectGrant::firstOrCreate([
            'model_type' => $this->getMorphClass(),
            'model_id' => $this->getKey(),
            'permission' => $permission,
        ]);

        if (method_exists($this, 'flushPermissions')) {
            $this->flushPermissions();
        }

        return $this;
    }

    /**
     * Revoke a direct grant for the given permission.
     */
    public function revokeGrant(string $permission): static
    {
        $this->activeGrants()
            ->where('permission', $permission)
            ->delete();

        if (method_exists($this, 'flushPermissions')) {
            $this->flushPermissions();
        }

        return $this;
    }
}
