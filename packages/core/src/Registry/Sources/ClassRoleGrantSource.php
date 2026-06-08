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
 * Primary source in Phase 1. Only roles with class_name (code roles).
 * Custom DB roles are covered by DatabaseRoleGrantSource.
 */
final class ClassRoleGrantSource implements GrantSource
{
    /** @var array<class-string, bool> */
    private static array $traitCache = [];

    public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet
    {
        if (! $this->hasAzGuardTrait($user)) {
            return PermissionSet::empty();
        }

        $keys = $user->roles
            ->filter(fn ($role) => $role->class_name !== null)
            ->map(fn ($role) => $role->getRoleLogic())
            ->filter()
            ->flatMap(function ($roleLogic) use ($panelId): array {
                $permissions = $roleLogic->permissions();
                $all = is_array($permissions) ? $permissions : [];

                return array_filter(
                    $all,
                    static fn (string $p) => $p === '*' || str_starts_with($p, $panelId . '.'),
                );
            })
            ->unique()
            ->values()
            ->all();

        return PermissionSet::fromRawKeys($keys);
    }

    public function priority(): int
    {
        return 100;
    }

    private function hasAzGuardTrait(Authenticatable $user): bool
    {
        return self::$traitCache[$user::class] ??=
            in_array(HasAzGuard::class, class_uses_recursive($user), strict: true);
    }
}
