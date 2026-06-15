<?php

declare(strict_types=1);

use AzGuard\Contracts\RoleInterface;
use AzGuard\Models\Role;
use AzGuard\Roles\BaseRole;

// A class with a permissions() method but NOT implementing the contract.
class NotARole
{
    public function permissions(): array
    {
        return ['*'];
    }
}

// A proper role.
class ProperRole extends BaseRole
{
    public function permissions(): array
    {
        return ['app.posts.view'];
    }
}

/**
 * B4: getRoleLogic() returns a RoleInterface or null — never a fatal and never
 * a duck-typed object that merely happens to expose permissions().
 */
describe('Role::getRoleLogic', function () {

    it('returns null when class_name is not set', function () {
        $role = new Role(['name' => 'x']);

        expect($role->getRoleLogic())->toBeNull();
    });

    it('returns null when class_name does not exist', function () {
        $role = new Role(['name' => 'x']);
        $role->class_name = 'AzGuard\\Nope\\Missing';

        expect($role->getRoleLogic())->toBeNull();
    });

    it('returns null when class_name does not implement RoleInterface', function () {
        $role = new Role(['name' => 'x']);
        $role->class_name = NotARole::class;

        expect($role->getRoleLogic())->toBeNull();
    });

    it('instantiates a RoleInterface implementation', function () {
        $role = new Role(['name' => 'x']);
        $role->class_name = ProperRole::class;

        expect($role->getRoleLogic())
            ->toBeInstanceOf(RoleInterface::class)
            ->and($role->getRoleLogic()->permissions())->toBe(['app.posts.view']);
    });
});
