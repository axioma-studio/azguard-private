<?php

declare(strict_types=1);

namespace AzGuard\Policies;

use AzGuard\Facades\AzGuard;
use AzGuard\Support\Panel;
use Illuminate\Contracts\Auth\Authenticatable;

trait AuthorizesPermission
{
    abstract protected function panelId(): string;

    protected function allows(\BackedEnum $permission, Authenticatable $user): bool
    {
        if (! method_exists($user, 'hasAzPermission')) {
            return false;
        }

        return $user->hasAzPermission(permission: $this->resolvePermission(permission: $permission));
    }

    protected function resolvePermission(\BackedEnum $permission): string
    {
        return $this->panel()->resolvePermission(permission: $permission);
    }

    protected function panel(): Panel
    {
        return AzGuard::panel(id: $this->panelId())
            ?? throw new \RuntimeException("Панель AzGuard [{$this->panelId()}] не зарегистрирована.");
    }
}
