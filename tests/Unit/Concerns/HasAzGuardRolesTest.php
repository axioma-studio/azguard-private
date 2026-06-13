<?php

declare(strict_types=1);

use AzGuard\Events\RoleAttached;
use AzGuard\Events\RoleDetached;
use AzGuard\Models\Role;
use AzGuard\Tests\Stubs\Roles\ManagerRole;
use AzGuard\Tests\Stubs\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('HasAzGuard — role management API', function (): void {

    it('assignRole attaches role and fires RoleAttached event', function (): void {
        Event::fake([RoleAttached::class]);

        $user = User::factory()->create();
        $role = Role::create([
            'name' => 'manager',
            'class_name' => ManagerRole::class,
            'level' => 10,
        ]);

        $user->assignRole('manager');
        $user->load('roles');

        expect($user->hasRole('manager'))->toBeTrue();

        Event::assertDispatched(RoleAttached::class, fn (RoleAttached $e): bool => $e->role->getKey() === $role->getKey(),
        );
    });

    it('assignRole with Role instance works the same', function (): void {
        Event::fake([RoleAttached::class]);

        $user = User::factory()->create();
        $role = Role::create([
            'name' => 'manager',
            'class_name' => ManagerRole::class,
            'level' => 10,
        ]);

        $user->assignRole($role);
        $user->load('roles');

        expect($user->hasRole('manager'))->toBeTrue();
        Event::assertDispatched(RoleAttached::class);
    });

    it('assignRole silently skips non-existent role name', function (): void {
        Event::fake([RoleAttached::class]);

        $user = User::factory()->create();
        $user->assignRole('ghost-role');

        expect($user->hasRole('ghost-role'))->toBeFalse();
        Event::assertNotDispatched(RoleAttached::class);
    });

    it('removeRole detaches role and fires RoleDetached event', function (): void {
        Event::fake([RoleDetached::class]);

        $user = User::factory()->create();
        $role = Role::create([
            'name' => 'manager',
            'class_name' => ManagerRole::class,
            'level' => 10,
        ]);

        $user->roles()->attach($role);
        $user->load('roles');

        $user->removeRole('manager');
        $user->load('roles');

        expect($user->hasRole('manager'))->toBeFalse();

        Event::assertDispatched(RoleDetached::class, fn (RoleDetached $e): bool => $e->role->getKey() === $role->getKey(),
        );
    });

    it('syncRoles replaces all roles firing detach and attach events', function (): void {
        Event::fake([RoleAttached::class, RoleDetached::class]);

        $user = User::factory()->create();

        $oldRole = Role::create([
            'name' => 'manager',
            'class_name' => ManagerRole::class,
            'level' => 10,
        ]);

        $newRole = Role::create([
            'name' => 'editor',
            'class_name' => ManagerRole::class,
            'level' => 5,
        ]);

        $user->roles()->attach($oldRole);
        $user->load('roles');

        $user->syncRoles(['editor']);
        $user->load('roles');

        expect($user->hasRole('manager'))->toBeFalse();
        expect($user->hasRole('editor'))->toBeTrue();

        Event::assertDispatched(RoleDetached::class);
        Event::assertDispatched(RoleAttached::class);
    });

    it('getRoleNames returns collection of role name strings', function (): void {
        $user = User::factory()->create();
        $role = Role::create([
            'name' => 'manager',
            'class_name' => ManagerRole::class,
            'level' => 10,
        ]);

        $user->roles()->attach($role);
        $user->load('roles');

        expect($user->getRoleNames()->toArray())->toBe(['manager']);
    });

    it('assignRole clears permissions cache', function (): void {
        $user = User::factory()->create();

        $role = Role::create([
            'name' => 'manager',
            'class_name' => ManagerRole::class,
            'level' => 10,
        ]);

        // Populate cache
        $user->load('roles');
        $before = $user->permissions('test');

        $user->assignRole($role);
        $user->load('roles');

        $after = $user->permissions('test');

        // Cache was cleared and rebuilt — permissions should differ
        expect($after->contains('test.post.view'))->toBeTrue();
    });
});
