<?php

namespace AzGuard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Role extends Model
{
    protected $fillable = ['name', 'level'];

    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            config('auth.providers.users.model'),
            'model',
            config('az-guard.table_names.model_has_roles')
        );
    }
}
