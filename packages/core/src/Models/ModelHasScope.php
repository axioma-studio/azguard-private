<?php

namespace AzGuard\Models;

use Illuminate\Database\Eloquent\Model;

class ModelHasScope extends Model
{
    protected $fillable = ['model_id', 'model_type', 'scope_entity_id', 'scope_entity_type', 'scope_class'];

    public function scope_entity()
    {
        return $this->morphTo();
    }
}
