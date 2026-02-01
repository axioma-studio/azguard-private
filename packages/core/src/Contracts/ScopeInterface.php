<?php

namespace AzGuard\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface ScopeInterface
{
    public function apply(Builder $builder, Model $user, ?Model $entity): void;
}
