<?php

declare(strict_types=1);

namespace AzGuard\Filament\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;

trait AuthorizesAzGuardPermissions
{
    protected function userCanAzPermission(string $permission): bool
    {
        $user = $this->authUser();

        if ($user === null || ! method_exists($user, 'hasPermission')) {
            return false;
        }

        return $user->hasPermission(permission: $permission);
    }

    protected function userHasAzRole(string $role): bool
    {
        $user = $this->authUser();

        if ($user === null || ! method_exists($user, 'hasRole')) {
            return false;
        }

        return $user->hasRole(name: $role);
    }

    protected function authUser(): ?Authenticatable
    {
        $user = auth()->user();

        return $user instanceof Authenticatable ? $user : null;
    }
}
