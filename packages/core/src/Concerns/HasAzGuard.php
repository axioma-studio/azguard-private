<?php

namespace AzGuard\Concerns;

trait HasAzGuard
{
    public function roles()
    {
        return $this->morphToMany(config('az-guard.models.role'), 'model', config('az-guard.table_names.model_has_roles'));
    }

    public function azScopes()
    {
        return $this->morphMany(config('az-guard.models.scope'), 'model');
    }

    public function hasAzRole(string $name)
    {
        return $this->roles->contains('name', $name);
    }
}
