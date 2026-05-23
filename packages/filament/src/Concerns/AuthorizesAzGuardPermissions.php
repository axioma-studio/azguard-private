<?php

declare(strict_types=1);

namespace AzGuard\Filament\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;

trait AuthorizesAzGuardPermissions
{
    protected function userCanAzPermission(string $permission): bool
    {
        $user = $this->authUser();

        if ($user === null || ! method_exists($user, 'hasAzPermission')) {
            return false;
        }

        return $user->hasAzPermission(permission: $permission);
    }

    protected function userHasAzRole(string $role): bool
    {
        $user = $this->authUser();

        if ($user === null || ! method_exists($user, 'hasAzRole')) {
            return false;
        }

        return $user->hasAzRole(name: $role);
    }

    protected function authUser(): ?Authenticatable
    {
        $user = auth()->user();

        return $user instanceof Authenticatable ? $user : null;
    }
}
