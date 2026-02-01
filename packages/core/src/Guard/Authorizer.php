<?php

namespace AzGuard\Guard;

use AzGuard\Contracts\RoleInterface;
use Illuminate\Contracts\Auth\Access\Authorizable;

class Authorizer
{
    public function check(Authorizable $user, string $ability): ?bool
    {
        foreach ($user->roles as $role) {
            if (class_exists($role->name)) {
                $roleClass = app($role->name);
                if ($roleClass instanceof RoleInterface && in_array($ability, $roleClass->permissions())) {
                    return true;
                }
            }
        }

        return null;
    }
}
