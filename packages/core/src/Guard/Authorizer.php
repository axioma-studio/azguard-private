<?php

declare(strict_types=1);

namespace AzGuard\Guard;

use AzGuard\Contracts\RoleInterface;
use Illuminate\Contracts\Auth\Access\Authorizable;

final class Authorizer
{
    public function check(Authorizable $user, string $ability): ?bool
    {
        unset($ability);

        if (! method_exists($user, 'roles')) {
            return null;
        }

        foreach ($user->roles as $role) {
            $roleClass = $role->class_name ?? $role->name;

            if (! is_string($roleClass) || ! class_exists($roleClass) || ! is_subclass_of($roleClass, RoleInterface::class)) {
                continue;
            }

            $roleInstance = app($roleClass);

            if ($this->hasWildcard(role: $roleInstance)) {
                return true;
            }
        }

        return null;
    }

    protected function hasWildcard(RoleInterface $role): bool
    {
        $permissions = $role->permissions();

        return in_array('*', $permissions, strict: true);
    }
}
