<?php

declare(strict_types=1);

namespace AzGuard\Registry\Sources;

use AzGuard\Concerns\HasAzGuard;
use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Источник grants из PHP-классов ролей (RoleInterface::permissions()).
 * Это основной источник в Фазе 1 — соответствует текущей логике
 * HasAzGuard::loadAzPermissions().
 *
 * Только роли с class_name (code roles). Custom DB roles — Фаза 3.
 */
final class ClassRoleGrantSource implements GrantSource
{
    public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet
    {
        if (! in_array(HasAzGuard::class, class_uses_recursive($user), strict: true)) {
            return PermissionSet::empty();
        }

        $keys = $user->roles
            ->filter(fn ($role) => $role->class_name !== null)
            ->map(fn ($role) => $role->getRoleLogic())
            ->filter()
            ->flatMap(function ($roleLogic) use ($panelId): array {
                $permissions = $roleLogic->permissions();
                $all = is_array($permissions) ? $permissions : [];

                // Фильтруем по панели: берём '*' и ключи с префиксом панели
                return array_filter(
                    $all,
                    static fn (string $p) => $p === '*' || str_starts_with($p, $panelId . '.'),
                );
            })
            ->unique()
            ->values()
            ->all();

        if (in_array('*', $keys, strict: true)) {
            return PermissionSet::wildcard();
        }

        return PermissionSet::fromKeys($keys);
    }

    public function priority(): int
    {
        return 100;
    }
}
