<?php

declare(strict_types=1);

namespace AzGuard\Registry\Sources;

use AzGuard\Concerns\HasRoles;
use AzGuard\Contracts\RoleInterface;
use AzGuard\Registry\Contracts\GrantPriority;
use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;
use Override;

/**
 * Grant source from PHP role classes (RoleInterface::permissions()).
 *
 * Only processes roles that have a class_name set (code-defined roles).
 * Pure DB roles without a class_name are handled by DatabaseRoleGrantSource.
 * Priority: 100 (highest).
 */
final class ClassRoleGrantSource implements GrantSource
{
    #[Override]
    public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet
    {
        // Gate on HasRoles (the trait that actually provides $user->roles), so
        // a user that uses HasRoles directly — not only the HasAzGuard composite
        // — still resolves its class roles.
        if (! in_array(HasRoles::class, class_uses_recursive($user), strict: true)) {
            return PermissionSet::empty();
        }

        $keys = $user->roles
            ->filter(fn ($role): bool => $role->class_name !== null)
            ->map(fn ($role) => $role->getRoleLogic())
            ->filter()
            ->flatMap(
                // Keep '*' (wildcard) and permissions prefixed with the current panel ID.
                fn (RoleInterface $roleLogic): array => array_filter(
                    $roleLogic->permissions(),
                    static fn (string $p): bool => $p === '*' || str_starts_with($p, $panelId.'.'),
                ))
            ->unique()
            ->values()
            ->all();

        if (in_array('*', $keys, strict: true)) {
            return PermissionSet::wildcard();
        }

        return PermissionSet::fromKeys($keys);
    }

    #[Override]
    public function priority(): int
    {
        return GrantPriority::ClassRole->value;
    }
}
