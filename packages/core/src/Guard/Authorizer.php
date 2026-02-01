<?php

namespace AzGuard\Guard;

use Illuminate\Contracts\Auth\Access\Authorizable;
use AzGuard\Contracts\RoleInterface;

class Authorizer
{
    public function check(Authorizable $user, string $ability): ?bool
    {
        // 1. Проверка через Role-Classes (Code-first)
        foreach ($user->roles as $roleRecord) {
            $roleName = $roleRecord->name;

            if (class_exists($roleName)) {
                $roleClass = app($roleName);
                if ($roleClass instanceof RoleInterface) {
                    if (in_array($ability, $roleClass->permissions()) || in_array('*', $roleClass->permissions())) {
                        return true;
                    }
                }
            }
        }

        // 2. Здесь можно добавить fallback на проверку разрешений напрямую из БД
        // return $user->permissions->contains('name', $ability);

        return null;
    }
}
