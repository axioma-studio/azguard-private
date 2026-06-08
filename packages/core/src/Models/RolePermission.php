<?php

declare(strict_types=1);

namespace AzGuard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Пермиссии, привязанные к DB-роли через таблицу.
 *
 * @property int    $id
 * @property int    $role_id
 * @property string $permission_key
 * @property string $panel_id
 */
class RolePermission extends Model
{
    public function getTable(): string
    {
        return config('az-guard.table_names.role_permissions', 'az_guard_role_permissions');
    }

    protected $fillable = [
        'role_id',
        'permission_key',
        'panel_id',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(config('az-guard.models.role'), 'role_id');
    }
}
