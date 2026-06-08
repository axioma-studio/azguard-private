<?php

declare(strict_types=1);

namespace AzGuard\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $grantable_type
 * @property int         $grantable_id
 * @property string      $panel_id
 * @property string      $permission_key
 * @property Carbon|null $expires_at
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
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

    public function getTable(): string
    {
        return (string) config('az-guard.table_names.direct_grants', 'az_direct_grants');
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function grantable(): MorphTo
    {
        return $this->morphTo();
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Только не истёкшие grants (бессрочные + expires_at > now).
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

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isExpired();
    }
}
