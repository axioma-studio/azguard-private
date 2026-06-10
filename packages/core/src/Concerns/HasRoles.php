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

    /**
     * Sync roles and fire attach/detach events.
     *
     * Loads all affected Role models in two batch queries (one for detached IDs,
     * one for attached IDs) instead of one query per ID, eliminating N+1.
     *
     * @param array<string|Role> $roles
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

        // Batch-load all affected models — 2 queries total instead of N.
        /** @var class-string<Role> $roleClass */
        $roleClass = Config::roleModel();

        if ($changes['detached'] !== []) {
            $detachedRoles = $roleClass::query()
                ->whereIn($roleClass::make()->getKeyName(), $changes['detached'])
                ->get()
                ->keyBy(fn (Role $r): mixed => $r->getKey());

            foreach ($changes['detached'] as $id) {
                if ($role = $detachedRoles->get($id)) {
                    event(new RoleDetached($this, $role));
                }
            }
        }

        if ($changes['attached'] !== []) {
            $attachedRoles = $roleClass::query()
                ->whereIn($roleClass::make()->getKeyName(), $changes['attached'])
                ->get()
                ->keyBy(fn (Role $r): mixed => $r->getKey());

            foreach ($changes['attached'] as $id) {
                if ($role = $attachedRoles->get($id)) {
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
