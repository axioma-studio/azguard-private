<?php

namespace AzGuard\Guard;

use AzGuard\Contracts\RoleInterface;
use Illuminate\Contracts\Auth\Access\Authorizable;

class Authorizer
{
    public function check(Authorizable $user, string $ability): ?bool
    {
        // Получаем все роли пользователя (и из БД, и потенциально через классы)
        foreach ($user->roles as $role) {
            // Если в поле name записан существующий класс роли
            if (class_exists($role->name) && is_subclass_of($role->name, RoleInterface::class)) {
                $roleInstance = app($role->name);

                if ($this->hasPermission($roleInstance, $ability)) {
                    return true;
                }
            }
        }

        return null; // Пропускаем проверку дальше (другим Gate или Policy)
    }

    protected function hasPermission(RoleInterface $role, string $ability): bool
    {
        $permissions = $role->permissions();

        return in_array('*', $permissions) || in_array($ability, $permissions);
    }
}
