<?php

declare(strict_types=1);

namespace AzGuard\Roles;

/**
 * Встроенная роль суперадмина.
 * Разрешение '*' даёт доступ ко всему через Gate::before().
 *
 * Использование:
 *   php artisan azguard:sync-roles
 *   $user->assignRole('super-admin');
 */
final class SuperAdminRole extends BaseRole
{
    public function getLevel(): int
    {
        return 1000;
    }

    public function permissions(): array
    {
        return ['*'];
    }
}
