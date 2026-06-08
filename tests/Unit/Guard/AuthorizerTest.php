<?php

declare(strict_types=1);

use AzGuard\Guard\Authorizer;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Collection;

/**
 * Создаём анонимный Authorizable с заданными правами для теста.
 */
function makeAuthorizable(array $permissions): Authorizable
{
    return new class ($permissions) implements Authorizable {
        private Collection $perms;

        public function __construct(array $permissions)
        {
            $this->perms = collect($permissions);
        }

        public function getAzPermissions(): Collection
        {
            return $this->perms;
        }

        public function can($abilities, $arguments = []) {}
        public function cant($abilities, $arguments = []) {}
        public function cannot($abilities, $arguments = []) {}
    };
}

describe('Authorizer', function () {
    it('returns null for user without getAzPermissions', function () {
        $user = new class implements Authorizable {
            public function can($abilities, $arguments = []) {}
            public function cant($abilities, $arguments = []) {}
            public function cannot($abilities, $arguments = []) {}
        };

        $authorizer = new Authorizer;
        expect($authorizer->check($user, 'some.ability'))->toBeNull();
    });

    it('returns true for superadmin with wildcard *', function () {
        $user = makeAuthorizable(['*']);
        $authorizer = new Authorizer;

        expect($authorizer->check($user, 'admin.users.delete'))->toBeTrue();
        expect($authorizer->check($user, 'any.ability'))->toBeTrue();
    });

    it('returns true when user has exact ability', function () {
        $user = makeAuthorizable(['admin.users.view', 'admin.posts.edit']);
        $authorizer = new Authorizer;

        expect($authorizer->check($user, 'admin.users.view'))->toBeTrue();
        expect($authorizer->check($user, 'admin.posts.edit'))->toBeTrue();
    });

    it('returns null when user does not have ability', function () {
        $user = makeAuthorizable(['admin.users.view']);
        $authorizer = new Authorizer;

        expect($authorizer->check($user, 'admin.users.delete'))->toBeNull();
    });

    it('returns null for empty permissions', function () {
        $user = makeAuthorizable([]);
        $authorizer = new Authorizer;

        expect($authorizer->check($user, 'admin.users.view'))->toBeNull();
    });

    it('matches wildcard pattern when feature enabled', function () {
        config(['az-guard.features.wildcard_permission' => true]);

        $user = makeAuthorizable(['admin.*']);
        $authorizer = new Authorizer;

        expect($authorizer->check($user, 'admin.users.view'))->toBeTrue();
        expect($authorizer->check($user, 'admin.posts.delete'))->toBeTrue();
        expect($authorizer->check($user, 'shop.orders.view'))->toBeNull();

        config(['az-guard.features.wildcard_permission' => false]);
    });

    it('does not match wildcard pattern when feature disabled', function () {
        config(['az-guard.features.wildcard_permission' => false]);

        $user = makeAuthorizable(['admin.*']);
        $authorizer = new Authorizer;

        // Должно вернуть null, а не true — паттерн не активен
        expect($authorizer->check($user, 'admin.users.view'))->toBeNull();
    });
});
