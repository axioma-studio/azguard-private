<?php

declare(strict_types=1);

namespace AzGuard\Grants;

use AzGuard\Events\GrantGiven;
use AzGuard\Events\GrantRevoked;
use AzGuard\Exceptions\PanelNotSetException;
use AzGuard\Models\DirectGrant;
use AzGuard\Support\PanelResolver;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Fluent builder for working with Direct Grants.
 *
 * Usage:
 *   AzGuard::forUser($user)->on('app')->ttl(3600)->give('app.documents.export');
 *   AzGuard::forUser($user)->on('app')->revoke('app.documents.export');
 *   AzGuard::forUser($user)->on('app')->list();
 */
final class GrantBuilder
{
    private ?string $panelId = null;

    private ?int $ttlSeconds = null;

    public function __construct(
        private readonly Authenticatable $user,
    ) {}

    // ─── Fluent setters ───────────────────────────────────────────────────────

    public function on(string $panelId): static
    {
        $this->panelId = $panelId;

        return $this;
    }

    /**
     * Set TTL in seconds. null = no expiry.
     */
    public function ttl(?int $seconds): static
    {
        $this->ttlSeconds = $seconds;

        return $this;
    }

    // ─── Actions ──────────────────────────────────────────────────────────────

    /**
     * Grant a permission (or update expires_at on an existing one).
     * Idempotent: repeated calls update expires_at only.
     *
     * @throws PanelNotSetException
     */
    public function give(string $permissionKey): DirectGrant
    {
        $panel = PanelResolver::resolveOrFail($this->panelId);

        $expiresAt = $this->ttlSeconds !== null
            ? Carbon::now()->addSeconds($this->ttlSeconds)
            : null;

        /** @var DirectGrant $grant */
        $grant = DirectGrant::query()->updateOrCreate(
            [
                'grantable_type' => $this->user::class,
                'grantable_id' => $this->user->getAuthIdentifier(),
                'panel_id' => $panel,
                'permission_key' => $permissionKey,
            ],
            ['expires_at' => $expiresAt],
        );

        event(new GrantGiven(
            user: $this->user,
            permissionKey: $permissionKey,
            panelId: $panel,
            grant: $grant,
        ));

        return $grant;
    }

    /**
     * Revoke a specific permission.
     *
     * @return int Number of deleted records (0 or 1).
     *
     * @throws PanelNotSetException
     */
    public function revoke(string $permissionKey): int
    {
        $panel = PanelResolver::resolveOrFail($this->panelId);
        $deleted = $this->baseQuery($panel)
            ->where('permission_key', $permissionKey)
            ->delete();

        if ($deleted > 0) {
            event(new GrantRevoked(
                user: $this->user,
                permissionKey: $permissionKey,
                panelId: $panel,
            ));
        }

        return (int) $deleted;
    }

    /**
     * Revoke all permissions for the user on the panel.
     *
     * @return int Number of deleted records.
     *
     * @throws PanelNotSetException
     */
    public function revokeAll(): int
    {
        $panel = PanelResolver::resolveOrFail($this->panelId);
        $deleted = $this->baseQuery($panel)->delete();

        if ($deleted > 0) {
            event(new GrantRevoked(
                user: $this->user,
                permissionKey: '*',
                panelId: $panel,
            ));
        }

        return (int) $deleted;
    }

    /**
     * Return all active grants for the user on the panel.
     *
     * @return Collection<int, DirectGrant>
     *
     * @throws PanelNotSetException
     */
    public function list(): Collection
    {
        return $this->baseQuery(PanelResolver::resolveOrFail($this->panelId))
            ->active()
            ->get();
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    /**
     * Base query scoped to this user + panel.
     * All mutating / read methods build on top of this.
     */
    private function baseQuery(string $panel): Builder
    {
        return DirectGrant::query()
            ->where('grantable_type', $this->user::class)
            ->where('grantable_id', $this->user->getAuthIdentifier())
            ->where('panel_id', $panel);
    }
}
