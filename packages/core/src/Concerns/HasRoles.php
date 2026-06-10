<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

use AzGuard\Events\RoleAttached;
use AzGuard\Events\RoleDetached;
use AzGuard\Models\Role;
use AzGuard\Support\Config;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

trait HasRoles
{
    use ResolvesRole;

    public function roles(): MorphToMany
    {
        return $this->morphToMany(
            Config::roleModel(),
            'model',
            Config::modelHasRolesTable(),
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

    /** @param array<string|Role> $roles */
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

        // Batch-load all affected roles in 1 query instead of N separate Role::find() calls.
        $allAffectedIds = array_merge($changes['detached'], $changes['attached']);

        if ($allAffectedIds !== []) {
            $roleModels = Role::whereIn('id', $allAffectedIds)->get()->keyBy('id');

            foreach ($changes['detached'] as $id) {
                if ($role = $roleModels->get($id)) {
                    event(new RoleDetached($this, $role));
                }
            }

            foreach ($changes['attached'] as $id) {
                if ($role = $roleModels->get($id)) {
                    event(new RoleAttached($this, $role));
                }
            }
        }

        $this->flushPermissions();
        $this->unsetRelation('roles');

        return $this;
    }

    /** @return Collection<int, string> */
    public function getRoleNames(): Collection
    {
        return $this->roles->pluck('name');
    }
}
