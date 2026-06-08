<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

use AzGuard\Events\RoleAttached;
use AzGuard\Events\RoleDetached;
use AzGuard\Models\Role;
use AzGuard\Registry\Resolver\EffectivePermissionResolver;
use AzGuard\Registry\Values\PermissionSet;
use AzGuard\Support\AzGuardContextBridge;
use AzGuard\Support\Config;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

/**
 * Trait for User model. Provides:
 * - relations: roles(), scopes()
 * - permission checks: hasPermission(), hasRole(), hasPermissionIn(), checkPermission()
 * - cache: permissionSet(), permissions(), flushPermissions()
 * - role management: assignRole(), removeRole(), syncRoles(), getRoleNames()
 */
trait HasAzGuard
{
    public function roles(): MorphToMany
    {
        return $this->morphToMany(
            Config::roleModel(),
            'model',
            Config::modelHasRolesTable()
        );
    }

    public function scopes()
    {
        return $this->morphMany(Config::scopeModel(), 'model');
    }

    public function hasRole(string $name): bool
    {
        return $this->roles->contains('name', $name);
    }

    /**
     * Check if user has a permission on a panel.
     *
     * Optional $context allows a one-off contextual check without changing
     * global state. Easier to use hasPermissionIn() as an alias.
     *
     * If azguard/context is not installed or $context is null —
     * behaviour is identical to a plain permission check.
     *
     * @param  object{contextType: string, contextId: int|string}|null  $context
     */
    public function hasPermission(string $permission, string $panelId = 'app', ?object $context = null): bool
    {
        if ($context !== null) {
            return AzGuardContextBridge::checkWithContext($this, $permission, $panelId, $context);
        }

        return $this->permissionSet($panelId)->grants($permission);
    }

    /**
     * Contextual permission check — does not mutate global state.
     *
     *   $user->hasPermissionIn('workspace', 42, 'app.posts.edit');
     *   $user->hasPermissionIn('workspace', 42, 'app.posts.edit', 'admin');
     */
    public function hasPermissionIn(
        string $contextType,
        int|string $contextId,
        string $permission,
        string $panelId = 'app',
    ): bool {
        return AzGuardContextBridge::checkInContext(
            user: $this,
            contextType: $contextType,
            contextId: $contextId,
            permission: $permission,
            panelId: $panelId,
        );
    }

    /**
     * Silent version: never throws. Use in Blade / UI.
     *
     * @param  object{contextType: string, contextId: int|string}|null  $context
     */
    public function checkPermission(string $permission, string $panelId = 'app', ?object $context = null): bool
    {
        try {
            return $this->hasPermission($permission, $panelId, $context);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get the PermissionSet for a panel.
     * All caching is delegated to EffectivePermissionResolver.
     */
    public function permissionSet(string $panelId = 'app'): PermissionSet
    {
        return app(EffectivePermissionResolver::class)->forUser($this, $panelId);
    }

    /**
     * Get all permission keys for a panel as a Collection.
     *
     * @return Collection<int, string>
     */
    public function permissions(string $panelId = 'app'): Collection
    {
        return collect($this->permissionSet($panelId)->toArray());
    }

    /**
     * Flush the permission cache for this user.
     * Called automatically by assignRole / removeRole / syncRoles.
     */
    public function flushPermissions(string $panelId = 'app'): void
    {
        app(EffectivePermissionResolver::class)->forgetForUser($this, $panelId);
    }

    /**
     * Assign one or more roles to the model.
     *
     * @param  string|Role  ...$roles
     */
    public function assignRole(string|Role ...$roles): static
    {
        foreach ($roles as $role) {
            $roleModel = $this->resolveRole($role);

            if ($roleModel === null) {
                continue;
            }

            $this->roles()->syncWithoutDetaching([$roleModel->getKey()]);
            $this->flushPermissions();
            event(new RoleAttached($this, $roleModel));
        }

        $this->unsetRelation('roles');

        return $this;
    }

    /**
     * Remove one or more roles from the model.
     *
     * @param  string|Role  ...$roles
     */
    public function removeRole(string|Role ...$roles): static
    {
        foreach ($roles as $role) {
            $roleModel = $this->resolveRole($role);

            if ($roleModel === null) {
                continue;
            }

            $this->roles()->detach($roleModel->getKey());
            $this->flushPermissions();
            event(new RoleDetached($this, $roleModel));
        }

        $this->unsetRelation('roles');

        return $this;
    }

    /**
     * Sync the set of roles on the model.
     *
     * @param  array<string|Role>  $roles
     */
    public function syncRoles(array $roles): static
    {
        foreach ($this->roles()->get() as $currentRole) {
            $this->roles()->detach($currentRole->getKey());
            event(new RoleDetached($this, $currentRole));
        }

        foreach ($roles as $role) {
            $roleModel = $this->resolveRole($role);
            if ($roleModel === null) {
                continue;
            }
            $this->roles()->attach($roleModel->getKey());
            event(new RoleAttached($this, $roleModel));
        }

        $this->flushPermissions();
        $this->unsetRelation('roles');

        return $this;
    }

    /**
     * Get all role names for the model.
     *
     * @return Collection<int, string>
     */
    public function getRoleNames(): Collection
    {
        return $this->roles->pluck('name');
    }

    /**
     * Resolve a Role model from a name string or Role instance.
     */
    protected function resolveRole(string|Role $role): ?Role
    {
        if ($role instanceof Role) {
            return $role;
        }

        /** @var class-string<Role> $roleClass */
        $roleClass = Config::roleModel();

        return $roleClass::query()->where('name', $role)->first();
    }
}
