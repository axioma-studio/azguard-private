<?php

declare(strict_types=1);

namespace AzGuard\Registry\Sources;

use AzGuard\Concerns\HasAzGuard;
use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Grant source from PHP role classes (RoleInterface::permissions()).
 *
 * Only processes roles that have a class_name set (code-defined roles).
 * Pure DB roles without a class_name are handled by DatabaseRoleGrantSource.
 * Priority: 100 (highest).
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

                // Keep '*' (wildcard) and permissions prefixed with the current panel ID.
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
