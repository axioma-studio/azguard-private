<?php

declare(strict_types=1);

namespace AzGuard\Models;

use AzGuard\Support\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * DB-level permissions assigned to a role via the role_permissions table.
 *
 * @property int $id
 * @property int $role_id
 * @property string $permission_key
 * @property string $panel_id
 */
class RolePermission extends Model
{
    #[Override]
    public function getTable(): string
    {
        return Config::rolePermissionsTable();
    }

    protected $fillable = [
        'role_id',
        'permission_key',
        'panel_id',
    ];

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Config::roleModel(), 'role_id');
    }
}
