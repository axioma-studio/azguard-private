<?php

use AzGuard\Models\Role;
use AzGuard\Tests\Stubs\Roles\ManagerRole;
use AzGuard\Tests\Stubs\User;
use Illuminate\Support\Facades\Gate;

test('user with ManagerRole can edit-users via Gate', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $role = Role::create([
        'name' => ManagerRole::class,
        'level' => 0,
    ]);

    $user->roles()->attach($role);

    expect(Gate::allows('edit-users', $user))->toBeTrue();
});
