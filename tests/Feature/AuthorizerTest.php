<?php

use AzGuard\Contracts\AzGuardManagerInterface;
use AzGuard\Models\Role;
use AzGuard\Support\Panel;
use AzGuard\Tests\Stubs\Roles\ManagerRole;
use AzGuard\Tests\Stubs\User;
use Illuminate\Support\Facades\Gate;

test('user with ManagerRole can access test.post.view via Gate', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $role = Role::create([
        'name' => 'manager',
        'class_name' => ManagerRole::class,
        'level' => 0,
    ]);

    $user->roles()->attach($role);
    $user->load('roles');

    $panel = Panel::make()->id('test');
    app(AzGuardManagerInterface::class)->setCurrentPanel($panel);

    $this->actingAs($user);

    expect(Gate::allows('test.post.view'))->toBeTrue();
    expect(Gate::allows('test.other.permission'))->toBeFalse();
});
