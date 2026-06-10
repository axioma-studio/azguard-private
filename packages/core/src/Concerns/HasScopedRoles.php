<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

use AzGuard\Models\ModelHasScope;
use AzGuard\Models\Role;
use AzGuard\Registry\Values\PermissionSet;
use AzGuard\Support\Config;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Adds entity-scoped role support to Eloquent models.
 *
 * Renamed from HasScopes (which conflicted with Laravel's own "scope"
 * terminology for Eloquent query scopes). HasScopes is kept as a
 * deprecated BC alias.
 *
 * Provides:
 * - assignScopedRole()    — assign a role scoped to a specific entity
 * - removeScopedRole()    — remove a scoped role assignment
 * - hasScopedRole()       — check if user has a role for a specific entity
 * - hasScopedPermission() — check permission within a specific entity scope
 *
 * Depends on HasAzGuard::resolveRole() being available on the same model.
 */
trait HasScopedRoles
{
    /**
     * The Eloquent global scope key used to filter query results
     * based on the authenticated user's scoped roles.
     *
     * Use this constant when calling Model::withoutGlobalScope():
     *   Model::withoutGlobalScope(HasScopedRoles::SCOPE_KEY);
     */
    public const SCOPE_KEY = 'azguard_scope_filter';

    public static function bootHasScopedRoles(): void
    {
        static::addGlobalScope(self::SCOPE_KEY, function (Builder $builder): void {
            if (app()->runningInConsole() || ! Auth::check()) {
                return;
            }

            $user = Auth::user();

            if (! method_exists($user, 'scopes')) {
                return;
            }

            $scopes = $user->scopes()
                ->where('scope_entity_type', static::class)
                ->get();

            foreach ($scopes as $scope) {
                if (class_exists($scope->scope_class)) {
                    app($scope->scope_class)->apply($builder, $user, $scope->scopeEntity);
                }
            }
        });
    }

    /**
     * Assign a role scoped to a specific entity.
     *
     *   $user->assignScopedRole('editor', $project);
     */
    public function assignScopedRole(string|Role $role, Model $entity): static
    {
        $roleModel = $this->resolveRole($role);

        if ($roleModel === null) {
            return $this;
        }

        ModelHasScope::firstOrCreate([
            'model_type' => $this->getMorphClass(),
            'model_id' => $this->getKey(),
            'scope_entity_type' => $entity->getMorphClass(),
            'scope_entity_id' => $entity->getKey(),
            'role_id' => $roleModel->getKey(),
        ], [
            'scope_class' => ($roleModel->getRoleLogic() ?? new class {})::class,
        ]);

        $this->flushPermissions();

        return $this;
    }

    /**
     * Remove a scoped role for a specific entity.
     *
     *   $user->removeScopedRole('editor', $project);
     */
    public function removeScopedRole(string|Role $role, Model $entity): static
    {
        $roleModel = $this->resolveRole($role);

        if ($roleModel === null) {
            return $this;
        }

        ModelHasScope::query()
            ->where('model_type', $this->getMorphClass())
            ->where('model_id', $this->getKey())
            ->where('scope_entity_type', $entity->getMorphClass())
            ->where('scope_entity_id', $entity->getKey())
            ->where('role_id', $roleModel->getKey())
            ->delete();

        $this->flushPermissions();

        return $this;
    }

    /**
     * Check if user has a specific role scoped to an entity.
     *
     *   $user->hasScopedRole('editor', $project);
     */
    public function hasScopedRole(string|Role $role, Model $entity): bool
    {
        $roleModel = $this->resolveRole($role);

        if ($roleModel === null) {
            return false;
        }

        return ModelHasScope::query()
            ->where('model_type', $this->getMorphClass())
            ->where('model_id', $this->getKey())
            ->where('scope_entity_type', $entity->getMorphClass())
            ->where('scope_entity_id', $entity->getKey())
            ->where('role_id', $roleModel->getKey())
            ->exists();
    }

    /**
     * Check if user has a permission within a specific entity scope.
     *
     *   $user->hasScopedPermission('app.projects.edit', $project);
     *
     * Resolution order:
     *   1. SuperAdmin global wildcard (*) — always granted via hasPermission()
     *   2. Scoped roles for the given entity — merged into a PermissionSet
     *      and checked via PermissionSet::grants() (supports wildcard patterns).
     */
    public function hasScopedPermission(string $permission, Model $entity): bool
    {
        // 1. SuperAdmin shortcut via global hasPermission().
        if (method_exists($this, 'hasPermission') && $this->hasPermission($permission)) {
            return true;
        }

        // 2. Collect scoped role IDs for this entity in one query.
        $scopedRoleIds = ModelHasScope::query()
            ->where('model_type', $this->getMorphClass())
            ->where('model_id', $this->getKey())
            ->where('scope_entity_type', $entity->getMorphClass())
            ->where('scope_entity_id', $entity->getKey())
            ->whereNotNull('role_id')
            ->pluck('role_id');

        if ($scopedRoleIds->isEmpty()) {
            return false;
        }

        /** @var class-string<Role> $roleClass */
        $roleClass = Config::roleModel();

        // 3. Build a merged PermissionSet from all scoped roles.
        //    This delegates wildcard and pattern matching to PermissionSet::grants()
        //    instead of duplicating the logic inline.
        $merged = PermissionSet::empty();

        foreach ($roleClass::query()->whereIn('id', $scopedRoleIds)->get() as $roleModel) {
            $logic = $roleModel->getRoleLogic();

            if ($logic === null) {
                continue;
            }

            $merged = $merged->merge(PermissionSet::fromRawKeys($logic->permissions()));

            // Short-circuit: if we already have a wildcard, no need to continue.
            if ($merged->isWildcard()) {
                break;
            }
        }

        return $merged->grants($permission);
    }
}
