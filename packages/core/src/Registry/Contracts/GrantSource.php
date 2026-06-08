<?php

declare(strict_types=1);

namespace AzGuard\Registry\Contracts;

use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Источник grants для пользователя в рамках панели.
 *
 * Фаза 1: ClassRoleGrantSource (PHP role classes).
 * Фаза 3: DatabaseRoleGrantSource, DirectGrantSource.
 * Фаза 4: ContextualRoleGrantSource.
 */
interface GrantSource
{
    /**
     * Вернуть PermissionSet для пользователя в данной панели.
     */
    public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet;

    /**
     * Приоритет при merge (больше = важнее).
     * ClassRole: 100, DatabaseRole: 90, DirectGrant: 80.
     */
    public function priority(): int;
}
