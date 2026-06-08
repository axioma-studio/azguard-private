<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

use AzGuard\Models\DirectGrant;
use AzGuard\Support\PanelResolver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait for Eloquent models (User, Admin, …).
 *
 * Adds:
 *  - directGrants()     — relation
 *  - hasDirectGrant()   — check for an active direct grant
 *  - activeDirectGrants() — all active grants for a panel
 *
 * Usage:
 *   class User extends Authenticatable
 *   {
 *       use HasAzGuard, HasDirectGrants;
 *   }
 *
 * Note: direct grants are already included by EffectivePermissionResolver
 * via DirectGrantSource. hasPermission() covers them automatically.
 * Use hasDirectGrant() only when you specifically need to check
 * the direct-grant layer in isolation (e.g. middleware, policy).
 */
trait HasDirectGrants
{
    // ─── Relation ─────────────────────────────────────────────────────────────

    /**
     * @return MorphMany<DirectGrant>
     */
    public function directGrants(): MorphMany
    {
        return $this->morphMany(DirectGrant::class, 'grantable');
    }

    // ─── Queries ──────────────────────────────────────────────────────────────

    /**
     * Check for an active direct grant for a specific permission key.
     *
     * @param  string       $permissionKey  e.g. 'app.documents.export'
     * @param  string|null  $panelId        Explicit panel; falls back to current AzGuard panel.
     */
    public function hasDirectGrant(string $permissionKey, ?string $panelId = null): bool
    {
        $panel = PanelResolver::resolve($panelId);

        if ($panel === null) {
            return false;
        }

        return $this->directGrants()
            ->where('panel_id', $panel)
            ->where('permission_key', $permissionKey)
            ->active()
            ->exists();
    }

    /**
     * Return all active grants for the given panel (or current panel).
     *
     * @return Collection<int, DirectGrant>
     */
    public function activeDirectGrants(?string $panelId = null): Collection
    {
        $panel = PanelResolver::resolve($panelId);

        $query = $this->directGrants()->active();

        if ($panel !== null) {
            $query->forPanel($panel);
        }

        return $query->get();
    }
}
