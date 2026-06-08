<?php

declare(strict_types=1);

namespace AzGuard\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Прямой grant permission пользователю (без роли).
 *
 * @property int         $id
 * @property string      $model_type
 * @property int|string  $model_id
 * @property string      $permission_key
 * @property string      $panel_id
 * @property \Carbon\Carbon|null $expires_at
 *
 * @method static Builder active()
 */
class DirectGrant extends Model
{
    public function getTable(): string
    {
        return config('az-guard.table_names.direct_grants', 'az_guard_direct_grants');
    }

    protected $fillable = [
        'model_type',
        'model_id',
        'permission_key',
        'panel_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Только активные grants: бессрочные или ещё не истекшие.
 *
     * @param  Builder<DirectGrant>  $query
     * @return Builder<DirectGrant>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Проверить, активен ли grant в настоящий момент.
     */
    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
