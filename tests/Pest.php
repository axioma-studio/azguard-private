<?php

declare(strict_types=1);

use AzGuard\Tests\TestCase;
use AzGuard\Tests\Stubs\User;

uses(TestCase::class)
    ->in('Feature', 'Unit');

function createUserWithPermissions(array $permissions): User
{
    /** @var User $user */
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        $user->giveAzPermission($permission);
    }

    return $user;
}

function createUserWithRole(string $roleName): User
{
    /** @var User $user */
    $user = User::factory()->create();

    $user->assignRole($roleName);

    return $user;
}
