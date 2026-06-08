<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

use AzGuard\Events\RoleAttached;
use AzGuard\Events\RoleDetached;
use AzGuard\Exceptions\AzGuardException;
use AzGuard\Models\Role;
use AzGuard\Registry\Resolver\EffectivePermissionResolver;
use AzGuard\Registry\Values\PermissionSet;
use AzGuard\Support\AzGuardContextBridge;
use AzGuard\Support\Config;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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

    public function scopes(): MorphMany
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
     * Only catches AzGuardException — PHP errors (TypeError, Error) still propagate.
     *
     * @param  object{contextType: string, contextId: int|string}|null  $context
     */
    public function checkPermission(string $permission, string $panelId = 'app', ?object $context = null): bool
    {
        try {
            return $this->hasPermission($permission, $panelId, $context);
        } catch (AzGuardException) {
            return false;
        }
    }

    /**
     * Get the PermissionSet for a panel.
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
     * Flush the permission cache for this user across ALL registered panels.
     */
    public function flushPermissions(?string $panelId = null): void
    {
        $resolver = app(EffectivePermissionResolver::class);

        if ($panelId !== null) {
            $resolver->forgetForUser($this, $panelId);
            return;
        }

        $panels = app(\AzGuard\AzGuardManager::class)->getPanels();

        foreach (array_keys($panels) as $id) {
            $resolver->forgetForUser($this, $id);
        }

        if (! isset($panels['app'])) {
            $resolver->forgetForUser($this, 'app');
        }
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
            event(new RoleAttached($this, $roleModel));
        }

        $this->flushPermissions();
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
            event(new RoleDetached($this, $roleModel));
        }

        $this->flushPermissions();
        $this->unsetRelation('roles');

        return $this;
    }

    /**
     * Sync roles. Single sync() call — no N flushes.
     *
     * @param  array<string|Role>  $roles
     */
    public function syncRoles(array $roles): static
    {
        $roleIds = [];

        foreach ($roles as $role) {
            $roleModel = $this->resolveRole($role);

            if ($roleModel !== null) {
                $roleIds[] = $roleModel->getKey();
            }
        }

        $changes = $this->roles()->sync($roleIds);

        foreach ($changes['detached'] as $id) {
            if ($detached = Role::find($id)) {
                event(new RoleDetached($this, $detached));
            }
        }

        foreach ($changes['attached'] as $id) {
            if ($attached = Role::find($id)) {
                event(new RoleAttached($this, $attached));
            }
        }

        $this->flushPermissions();
        $this->unsetRelation('roles');

        return $this;
    }

    /**
     * @return Collection<int, string>
     */
    public function getRoleNames(): Collection
    {
        return $this->roles->pluck('name');
    }

    /**
     * Resolve a Role model from name or instance.
     */
    protected function resolveRole(string|Role $role): ?Role
    {
        if ($role instanceof Role) {
            return $role;
        }

        /** @var class-string<Role> $roleClass */
        $roleClass = Config::roleModel();

        return $roleClass::findByName($role);
    }
}
