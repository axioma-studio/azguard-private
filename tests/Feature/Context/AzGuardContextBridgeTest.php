<?php

declare(strict_types=1);

use AzGuard\Context\AuthorizationContext;
use AzGuard\Context\AuthorizationContextManager;
use AzGuard\Support\AzGuardContextBridge;
use AzGuard\Tests\Stubs\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->manager = app(AuthorizationContextManager::class);
    $this->manager->clearAll();
});

it('checkInContext() returns false when user has no context permissions', function () {
    $user = User::factory()->create();

    $result = AzGuardContextBridge::checkInContext(
        user: $user,
        contextType: 'workspace',
        contextId: 42,
        permission: 'app.posts.edit',
        panelId: 'app',
    );

    expect($result)->toBeFalse();
});

it('checkInContext() returns true when permission exists in DB for context', function () {
    $user = User::factory()->create();

    DB::table('az_guard_context_roles')->insert([
        'model_type' => User::class,
        'model_id' => $user->getAuthIdentifier(),
        'context_type' => 'workspace',
        'context_id' => 42,
        'panel_id' => 'app',
        'permission_key' => 'app.posts.edit',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $result = AzGuardContextBridge::checkInContext(
        user: $user,
        contextType: 'workspace',
        contextId: 42,
        permission: 'app.posts.edit',
        panelId: 'app',
    );

    expect($result)->toBeTrue();
});

it('checkInContext() restores previous global context after isolated check', function () {
    $user = User::factory()->create();
    $original = new AuthorizationContext('app', 'project', 99);
    $this->manager->set($original);

    AzGuardContextBridge::checkInContext(
        user: $user,
        contextType: 'workspace',
        contextId: 42,
        permission: 'app.posts.edit',
        panelId: 'app',
    );

    $restored = $this->manager->current('app');

    expect($restored)->not->toBeNull()
        ->and($restored->contextType)->toBe('project')
        ->and($restored->contextId)->toBe(99);
});

it('checkInContext() clears context if none existed before', function () {
    $user = User::factory()->create();

    expect($this->manager->has('app'))->toBeFalse();

    AzGuardContextBridge::checkInContext(
        user: $user,
        contextType: 'workspace',
        contextId: 42,
        permission: 'app.posts.edit',
        panelId: 'app',
    );

    expect($this->manager->has('app'))->toBeFalse();
});

it('hasAzPermissionIn() leaves global context unchanged', function () {
    $user = User::factory()->create();

    expect($this->manager->has('app'))->toBeFalse();

    $user->hasAzPermissionIn('workspace', 42, 'app.posts.edit', 'app');

    expect($this->manager->has('app'))->toBeFalse();
});

it('checkWithContext() accepts duck-typed context object', function () {
    $user = User::factory()->create();

    $fakeContext = new stdClass;
    $fakeContext->contextType = 'workspace';
    $fakeContext->contextId = 42;

    $result = AzGuardContextBridge::checkWithContext(
        user: $user,
        permission: 'app.posts.edit',
        panelId: 'app',
        context: $fakeContext,
    );

    expect($result)->toBeBool();
});
