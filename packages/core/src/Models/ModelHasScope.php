<?php

declare(strict_types=1);

namespace AzGuard\Models;

use AzGuard\Support\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Entity-scoped role assignment.
 *
 * Represents a role granted to a model (e.g. User) scoped to a specific entity
 * (e.g. Project, Team). The scope_class defines optional query-scope behaviour.
 *
 * @property int $id
 * @property string $model_type
 * @property int $model_id
 * @property string|null $scope_entity_type
 * @property int|null $scope_entity_id
 * @property string $scope_class
 * @property int|null $role_id
 */
class ModelHasScope extends Model
{
    protected $fillable = [
        'model_id',
        'model_type',
        'scope_entity_id',
        'scope_entity_type',
        'scope_class',
        'role_id',
    ];

    /** @return MorphTo<Model, $this> */
    public function scopeEntity(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Config::roleModel());
    }
}
