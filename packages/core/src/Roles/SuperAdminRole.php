<?php

declare(strict_types=1);

namespace AzGuard\Roles;

use Override;

/**
 * Встроенная роль суперадмина.
 * Разрешение '*' даёт доступ ко всему через Gate::before().
 *
 * Использование:
 *   php artisan guard:sync-roles
 *   $user->assignRole('super-admin');
 */
final class SuperAdminRole extends BaseRole
{
    #[Override]
    public function getLevel(): int
    {
        return 1000;
    }

    /** @return list<string> */
    #[Override]
    public function permissions(): array
    {
        return ['*'];
    }
}
