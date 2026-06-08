<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

use AzGuard\Models\ModelHasScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Adds entity-scoped role support to Eloquent models.
 *
 * When a model uses this trait, a global scope is applied that filters
 * results based on the authenticated user's scoped roles for this entity type.
 *
 * Also provides:
 * - assignScopedRole() — assign a role scoped to a specific entity
 * - removeScopedRole() — remove a scoped role assignment
 * - hasScopedRole()   — check if user has a role for a specific entity
 * - hasScopedPermission() — check permission within a specific entity scope
 */
trait InteractsWithAzScopes
{
    public static function bootInteractsWithAzScopes(): void
    {
        static::addGlobalScope('az_guard_filter', function (Builder $builder): void {
            if (app()->runningInConsole() || ! Auth::check()) {
                return;
            }

            $user = Auth::user();

            if (! method_exists($user, 'azScopes')) {
                return;
            }

            $scopes = $user->azScopes()
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
     *
     * @param  string|\AzGuard\Models\Role  $role
     * @param  Model  $entity
     */
    public function assignScopedRole(string|\AzGuard\Models\Role $role, Model $entity): static
    {
        $roleModel = $this->resolveScopeRole($role);

        if ($roleModel === null) {
            return $this;
        }

        ModelHasScope::firstOrCreate([
            'model_type'        => $this->getMorphClass(),
            'model_id'          => $this->getKey(),
            'scope_entity_type' => $entity->getMorphClass(),
            'scope_entity_id'   => $entity->getKey(),
            'role_id'           => $roleModel->getKey(),
        ], [
            'scope_class' => get_class($roleModel->getRoleLogic() ?? new class {}),
        ]);

        $this->clearAzPermissionsCache();

        return $this;
    }

    /**
     * Remove a scoped role for a specific entity.
     *
     *   $user->removeScopedRole('editor', $project);
     */
    public function removeScopedRole(string|\AzGuard\Models\Role $role, Model $entity): static
    {
        $roleModel = $this->resolveScopeRole($role);

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

        $this->clearAzPermissionsCache();

        return $this;
    }

    /**
     * Check if user has a specific role scoped to an entity.
     *
     *   $user->hasScopedRole('editor', $project);
     */
    public function hasScopedRole(string|\AzGuard\Models\Role $role, Model $entity): bool
    {
        $roleModel = $this->resolveScopeRole($role);

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
     * Order of resolution:
     *   1. SuperAdmin global wildcard (*) — always granted
     *   2. Global roles (from HasAzGuard::hasAzPermission)
     *   3. Scoped roles for the given entity
     */
    public function hasScopedPermission(string $permission, Model $entity): bool
    {
        // 1. SuperAdmin global wildcard
        if (method_exists($this, 'hasAzPermission') && $this->hasAzPermission($permission)) {
            return true;
        }

        // 2. Check scoped roles for this entity
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

        /** @var class-string<\AzGuard\Models\Role> $roleClass */
        $roleClass = config('az-guard.models.role');

        $roles = $roleClass::query()->whereIn('id', $scopedRoleIds)->get();

        foreach ($roles as $roleModel) {
            $logic = $roleModel->getRoleLogic();

            if ($logic === null) {
                continue;
            }

            $permissions = $logic->permissions();

            if (in_array('*', $permissions, true) || in_array($permission, $permissions, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve a Role model from a string name or Role instance.
     */
    protected function resolveScopeRole(string|\AzGuard\Models\Role $role): ?\AzGuard\Models\Role
    {
        if ($role instanceof \AzGuard\Models\Role) {
            return $role;
        }

        /** @var class-string<\AzGuard\Models\Role> $roleClass */
        $roleClass = config('az-guard.models.role');

        return $roleClass::query()->where('name', $role)->first();
    }
}
