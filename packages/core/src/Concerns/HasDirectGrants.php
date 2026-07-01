<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

use AzGuard\Models\DirectGrant;
use AzGuard\Support\PanelResolver;
use AzGuard\Support\PermissionName;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use UnitEnum;

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
     *
     * $permission may be a key string or a permission enum (scoped to $panelId).
     * When $panelId is null the default panel is used (az-guard.default_panel,
     * else 'app'), consistent with hasPermission() — the grant is always scoped
     * to a resolved panel, never matched across panels with a raw key.
     */
    public function hasGrant(string|UnitEnum $permission, ?string $panelId = null): bool
    {
        $panelId = PanelResolver::resolveDefault($panelId);

        return $this->directGrants()
            ->where('panel_id', $panelId)
            ->where('permission_key', PermissionName::resolve($permission, $panelId))
            ->active()
            ->exists();
    }

    /**
     * Assign a direct grant for the given permission in a panel.
     *
     * Idempotent: an existing grant has its expires_at updated (so a permanent
     * grant can be given a TTL and vice-versa). $permission may be a key string
     * or a permission enum (scoped to $panelId).
     */
    public function grant(string|UnitEnum $permission, string $panelId, ?DateTimeInterface $expiresAt = null): static
    {
        $this->directGrants()->updateOrCreate(
            ['panel_id' => $panelId, 'permission_key' => PermissionName::resolve($permission, $panelId)],
            ['expires_at' => $expiresAt],
        );

        if (method_exists($this, 'flushPermissions')) {
            $this->flushPermissions($panelId);
        }

        return $this;
    }

    /**
     * Revoke a direct grant for the given permission in a panel.
     *
     * $permission may be a key string or a permission enum (scoped to $panelId).
     */
    public function revoke(string|UnitEnum $permission, string $panelId): static
    {
        $this->directGrants()
            ->where('panel_id', $panelId)
            ->where('permission_key', PermissionName::resolve($permission, $panelId))
            ->delete();

        if (method_exists($this, 'flushPermissions')) {
            $this->flushPermissions($panelId);
        }

        return $this;
    }
}
