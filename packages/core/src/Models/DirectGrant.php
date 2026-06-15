<?php

declare(strict_types=1);

namespace AzGuard\Models;

use AzGuard\Registry\Resolver\PermissionCache;
use AzGuard\Support\Config;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property string $grantable_type
 * @property int $grantable_id
 * @property string $panel_id
 * @property string $permission_key
 * @property Carbon|null $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder<self> active()
 * @method static Builder<self> forPanel(string $panelId)
 */
class DirectGrant extends Model
{
    protected $fillable = [
        'grantable_type',
        'grantable_id',
        'panel_id',
        'permission_key',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Flush the grantable's cached permissions whenever a grant is written or
     * deleted — covers the Filament resource, raw model saves/deletes, and any
     * other model-event path. The grantable_id is the cache user id, so no model
     * load is needed. (GrantBuilder's bulk revoke fires GrantRevoked instead.)
     */
    #[Override]
    protected static function booted(): void
    {
        $flush = static function (self $grant): void {
            app(PermissionCache::class)->forgetForUser($grant->grantable_id, $grant->panel_id);
        };

        static::created($flush);
        static::updated($flush);
        static::deleted($flush);
    }

    #[Override]
    public function getTable(): string
    {
        return Config::directGrantsTable();
    }

    // ─── Relations ───────────────────────────────────────────────────────────

    /** @return MorphTo<Model, $this> */
    public function grantable(): MorphTo
    {
        return $this->morphTo();
    }

    // ─── Scopes ────────────────────────────────────────────────────────────

    /**
     * Only non-expired grants: no expiry date OR expires_at > now().
     *
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where(function (Builder $q): void {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForPanel(Builder $query, string $panelId): void
    {
        $query->where('panel_id', $panelId);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isExpired();
    }
}
