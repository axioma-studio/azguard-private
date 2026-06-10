<?php

declare(strict_types=1);

use AzGuard\Tests\ContextTestCase;
use AzGuard\Tests\Stubs\User;
use AzGuard\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class)
    ->in('Unit');

uses(TestCase::class, RefreshDatabase::class)
    ->in('Feature/AccessControlTest.php',
        'Feature/AuthorizerExtendedTest.php',
        'Feature/AuthorizerTest.php',
        'Feature/CacheResetCommandTest.php',
        'Feature/CheckAccessMiddlewareTest.php',
        'Feature/DatabaseRoleGrantSourceTest.php',
        'Feature/DiscoveryTest.php',
        'Feature/DoctorCommandTest.php',
        'Feature/GateIntegrationScopedTest.php',
        'Feature/HasDirectGrantsTest.php',
        'Feature/ListScopedRolesCommandTest.php',
        'Feature/LoadAzGuardRolesMiddlewareTest.php',
        'Feature/MakeGuardPanelCommandTest.php',
        'Feature/PanelPermissionResolverTest.php',
        'Feature/PermissionAccessTest.php',
        'Feature/PermissionMapTest.php',
        'Feature/PolicyAttributeRegistrarTest.php',
        'Feature/SetCurrentPanelMiddlewareTest.php',
        'Feature/SyncRolesCommandTest.php',
    );

uses(ContextTestCase::class, RefreshDatabase::class)
    ->in('Feature/Context');

uses(RefreshDatabase::class)
    ->in('Unit');

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
