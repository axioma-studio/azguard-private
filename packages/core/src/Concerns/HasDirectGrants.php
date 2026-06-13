<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

use AzGuard\Models\DirectGrant;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Adds direct-grant support to a User model.
 *
 * Direct grants are one-off permission assignments that bypass roles.
 * They are checked by DirectGrantSource inside EffectivePermissionResolver.
 *
 * Usage:
 *   $user->directGrants()->create(['panel_id' => 'app', 'permission_key' => 'app.posts.edit']);
 *   $user->hasGrant('app.posts.edit', 'app'); // true
 *   $user->grants('app'); // Collection of active grants
 */
trait HasDirectGrants
{
    /**
     * All direct grants belonging to this model (morph relation).
     */
    public function directGrants(): MorphMany
    {
        return $this->morphMany(DirectGrant::class, 'grantable');
    }

    /**
     * Active (non-expired) grants for a specific panel.
     *
     * @return Collection<int, DirectGrant>
     */
    public function grants(string $panelId): Collection
    {
        return $this->directGrants()
            ->where('panel_id', $panelId)
            ->active()
            ->get();
    }

    /**
     * Check whether this model has a specific active direct grant for a panel.
     */
    public function hasGrant(string $permission, ?string $panelId = null): bool
    {
        $query = $this->directGrants()
            ->where('permission_key', $permission)
            ->active();

        if ($panelId !== null) {
            $query = $query->where('panel_id', $panelId);
        }

        return $query->exists();
    }

    /**
     * Assign a direct grant for the given permission in a panel.
     * Silently ignores duplicates via firstOrCreate.
     */
    public function grant(string $permission, string $panelId, ?DateTimeInterface $expiresAt = null): static
    {
        $this->directGrants()->firstOrCreate(
            ['panel_id' => $panelId, 'permission_key' => $permission],
            ['expires_at' => $expiresAt],
        );

        if (method_exists($this, 'flushPermissions')) {
            $this->flushPermissions($panelId);
        }

        return $this;
    }

    /**
     * Revoke a direct grant for the given permission in a panel.
     */
    public function revoke(string $permission, string $panelId): static
    {
        $this->directGrants()
            ->where('panel_id', $panelId)
            ->where('permission_key', $permission)
            ->delete();

        if (method_exists($this, 'flushPermissions')) {
            $this->flushPermissions($panelId);
        }

        return $this;
    }
}
