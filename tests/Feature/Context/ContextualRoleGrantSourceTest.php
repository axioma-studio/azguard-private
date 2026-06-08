<?php

declare(strict_types=1);

use AzGuard\Context\AuthorizationContext;
use AzGuard\Context\AuthorizationContextManager;
use AzGuard\Context\ContextualRoleGrantSource;
use AzGuard\Context\Strategies\DenyWithoutContextStrategy;
use AzGuard\Context\Strategies\GlobalPlusContextStrategy;
use AzGuard\Tests\Stubs\User;
use Illuminate\Support\Facades\DB;

uses(AzGuard\Tests\ContextTestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->manager = app(AuthorizationContextManager::class);
    $this->user    = User::factory()->create();
});

afterEach(function () {
    $this->manager->clearAll();
});

it('returns empty set when no context set and strategy is DenyWithoutContext', function () {
    $source = new ContextualRoleGrantSource(
        $this->manager,
        new DenyWithoutContextStrategy,
    );

    $result = $source->permissionsFor($this->user, 'app');

    expect($result->isEmpty())->toBeTrue();
});

it('returns empty set when context is set but user has no rows', function () {
    $this->manager->set(new AuthorizationContext('app', 'workspace', 42));

    $source = new ContextualRoleGrantSource(
        $this->manager,
        new GlobalPlusContextStrategy,
    );

    $result = $source->permissionsFor($this->user, 'app');

    expect($result->isEmpty())->toBeTrue();
});

it('returns permissions from DB when context matches', function () {
    $this->manager->set(new AuthorizationContext('app', 'workspace', 42));

    DB::table('az_guard_context_roles')->insert([
        'model_type'     => User::class,
        'model_id'       => $this->user->getAuthIdentifier(),
        'context_type'   => 'workspace',
        'context_id'     => 42,
        'panel_id'       => 'app',
        'permission_key' => 'app.posts.edit',
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);

    $source = new ContextualRoleGrantSource(
        $this->manager,
        new GlobalPlusContextStrategy,
    );

    $result = $source->permissionsFor($this->user, 'app');

    expect($result->grants('app.posts.edit'))->toBeTrue()
        ->and($result->grants('app.posts.delete'))->toBeFalse();
});

it('wildcard permission grants everything', function () {
    $this->manager->set(new AuthorizationContext('app', 'workspace', 42));

    DB::table('az_guard_context_roles')->insert([
        'model_type'     => User::class,
        'model_id'       => $this->user->getAuthIdentifier(),
        'context_type'   => 'workspace',
        'context_id'     => 42,
        'panel_id'       => 'app',
        'permission_key' => '*',
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);

    $source = new ContextualRoleGrantSource(
        $this->manager,
        new GlobalPlusContextStrategy,
    );

    $result = $source->permissionsFor($this->user, 'app');

    expect($result->grants('anything.at.all'))->toBeTrue();
});

it('does not return permissions for different contextId', function () {
    $this->manager->set(new AuthorizationContext('app', 'workspace', 99));

    DB::table('az_guard_context_roles')->insert([
        'model_type'     => User::class,
        'model_id'       => $this->user->getAuthIdentifier(),
        'context_type'   => 'workspace',
        'context_id'     => 42,
        'panel_id'       => 'app',
        'permission_key' => 'app.posts.edit',
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);

    $source = new ContextualRoleGrantSource(
        $this->manager,
        new GlobalPlusContextStrategy,
    );

    $result = $source->permissionsFor($this->user, 'app');

    expect($result->grants('app.posts.edit'))->toBeFalse();
});

it('does not return permissions for different contextType', function () {
    $this->manager->set(new AuthorizationContext('app', 'project', 42));

    DB::table('az_guard_context_roles')->insert([
        'model_type'     => User::class,
        'model_id'       => $this->user->getAuthIdentifier(),
        'context_type'   => 'workspace',
        'context_id'     => 42,
        'panel_id'       => 'app',
        'permission_key' => 'app.posts.edit',
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);

    $source = new ContextualRoleGrantSource(
        $this->manager,
        new GlobalPlusContextStrategy,
    );

    $result = $source->permissionsFor($this->user, 'app');

    expect($result->grants('app.posts.edit'))->toBeFalse();
});

it('has priority 95', function () {
    $source = new ContextualRoleGrantSource(
        $this->manager,
        new GlobalPlusContextStrategy,
    );

    expect($source->priority())->toBe(95);
});
