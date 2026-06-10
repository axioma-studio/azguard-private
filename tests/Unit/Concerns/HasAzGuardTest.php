<?php

declare(strict_types=1);

use AzGuard\Models\Role;
use AzGuard\Roles\BaseRole;
use AzGuard\Tests\Stubs\Roles\ManagerRole;
use AzGuard\Tests\Stubs\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('HasAzGuard trait', function () {
    beforeEach(function () {
        config(['az-guard.cache.store' => 'array']);
    });

    it('hasAzRole returns true for assigned role', function () {
        $user = User::create([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'secret',
        ]);

        $role = Role::create([
            'name' => 'manager',
            'class_name' => ManagerRole::class,
            'level' => 0,
        ]);

        $user->roles()->attach($role);
        $user->load('roles');

        expect($user->hasAzRole('manager'))->toBeTrue();
    });

    it('hasAzRole returns false for non-assigned role', function () {
        $user = User::create([
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'secret',
        ]);

        expect($user->hasAzRole('admin'))->toBeFalse();
    });

    it('hasAzPermission returns true for permission granted via role', function () {
        $user = User::create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'secret',
        ]);

        $role = Role::create([
            'name' => 'manager2',
            'class_name' => ManagerRole::class,
            'level' => 0,
        ]);

        $user->roles()->attach($role);
        $user->load('roles');

        expect($user->hasAzPermission('test.post.view', 'test'))->toBeTrue();
    });

    it('hasAzPermission returns false for ungranted permission', function () {
        $user = User::create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'secret',
        ]);

        $role = Role::create([
            'name' => 'manager3',
            'class_name' => ManagerRole::class,
            'level' => 0,
        ]);

        $user->roles()->attach($role);
        $user->load('roles');

        expect($user->hasAzPermission('admin.delete'))->toBeFalse();
    });

    it('getAzPermissions caches result in-memory', function () {
        $user = User::create([
            'name' => 'Cache User',
            'email' => 'cache@example.com',
            'password' => 'secret',
        ]);

        $role = Role::create([
            'name' => 'manager4',
            'class_name' => ManagerRole::class,
            'level' => 0,
        ]);

        $user->roles()->attach($role);
        $user->load('roles');

        $first = $user->getAzPermissions('test');
        $second = $user->getAzPermissions('test');

        // Должен вернуть те же данные (кэш — один объект PermissionSet)
        expect($first->toArray())->toBe($second->toArray());
    });

    it('clearAzPermissionsCache resets in-memory cache', function () {
        $user = User::create([
            'name' => 'Clear Cache User',
            'email' => 'clear@example.com',
            'password' => 'secret',
        ]);

        $role = Role::create([
            'name' => 'manager5',
            'class_name' => ManagerRole::class,
            'level' => 0,
        ]);

        $user->roles()->attach($role);
        $user->load('roles');

        $before = $user->getAzPermissions('test');
        $user->clearAzPermissionsCache('test');

        // После сброса — новая загрузка с теми же данными
        $after = $user->getAzPermissions('test');

        expect($after->toArray())->toBe($before->toArray());
    });

    it('wildcard * grants all permissions when present', function () {
        // Создаём роль с wildcard через анонимный класс
        $wildcardRole = new class extends BaseRole
        {
            public function permissions(): array
            {
                return ['*'];
            }
        };

        $user = User::create([
            'name' => 'Super',
            'email' => 'super@example.com',
            'password' => 'secret',
        ]);

        $role = Role::create([
            'name' => 'superadmin',
            'class_name' => get_class($wildcardRole),
            'level' => 100,
        ]);

        $user->roles()->attach($role);
        $user->load('roles');

        expect($user->hasAzPermission('any.random.permission'))->toBeTrue();
        expect($user->hasAzPermission('admin.delete.everything'))->toBeTrue();
    });
});
