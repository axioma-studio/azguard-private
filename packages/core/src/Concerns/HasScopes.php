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
use Illuminate\Support\Facades\DB;

/**
 * Adds entity-scoped role support to Eloquent models.
 *
 * Requires HasAzGuard on the same model (uses resolveRole() from it).
 *
 * @method ?Role resolveRole(string|Role $role) Inherited from HasAzGuard.
 */
trait HasScopes
{
    public static function bootHasScopes(): void
    {
        static::addGlobalScope('az_guard_filter', function (Builder $builder): void {
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
            'model_type'        => $this->getMorphClass(),
            'model_id'          => $this->getKey(),
            'scope_entity_type' => $entity->getMorphClass(),
            'scope_entity_id'   => $entity->getKey(),
            'role_id'           => $roleModel->getKey(),
        ], [
            'scope_class' => get_class($roleModel->getRoleLogic() ?? new class {}),
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
     *   2. Scoped roles for the given entity (1 JOIN query)
     */
    public function hasScopedPermission(string $permission, Model $entity): bool
    {
        if (method_exists($this, 'hasPermission') && $this->hasPermission($permission)) {
            return true;
        }

        $scopesTable = Config::modelHasScopesTable();
        $rolesTable  = Config::rolesTable();

        // Single JOIN: model_has_scopes ✕ roles → permission keys
        $keys = DB::table($scopesTable)
            ->join($rolesTable, "{$rolesTable}.id", '=', "{$scopesTable}.role_id")
            ->where("{$scopesTable}.model_type", $this->getMorphClass())
            ->where("{$scopesTable}.model_id", $this->getKey())
            ->where("{$scopesTable}.scope_entity_type", $entity->getMorphClass())
            ->where("{$scopesTable}.scope_entity_id", $entity->getKey())
            ->whereNotNull("{$scopesTable}.role_id")
            ->pluck("{$rolesTable}.permission_keys") // JSON column or CSV
            ->flatMap(fn ($raw) => is_array($raw) ? $raw : json_decode((string) $raw, true) ?? [])
            ->all();

        return PermissionSet::fromRawKeys($keys)->grants($permission);
    }
}
