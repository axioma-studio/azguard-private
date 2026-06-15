<?php

declare(strict_types=1);

use AzGuard\Models\Role;
use AzGuard\Tests\Stubs\User;

/**
 * H1: a wildcard-pattern grant ('test.post.*') must survive catalog filtering
 * and grant the concrete keys it covers — but only when the wildcard feature is
 * enabled. With it off, the pattern is treated as an unknown key and dropped.
 */
beforeEach(function () {
    $this->user = User::factory()->create();

    $role = Role::create(['name' => 'post-editor', 'level' => 0]);
    $role->dbPermissions()->create(['permission_key' => 'test.post.*', 'panel_id' => 'test']);

    $this->user->assignRole('post-editor');
});

it('honours a prefix.* grant when the wildcard feature is enabled', function () {
    config()->set('az-guard.features.wildcard_permission', true);

    expect($this->user->hasPermission('test.post.view', 'test'))->toBeTrue()
        ->and($this->user->hasPermission('test.post.delete', 'test'))->toBeTrue()
        // A key outside the pattern's namespace is not granted.
        ->and($this->user->hasPermission('test.other.view', 'test'))->toBeFalse();
});

it('drops a prefix.* grant when the wildcard feature is disabled', function () {
    config()->set('az-guard.features.wildcard_permission', false);

    expect($this->user->hasPermission('test.post.view', 'test'))->toBeFalse();
});
