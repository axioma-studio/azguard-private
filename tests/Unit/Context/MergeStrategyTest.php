<?php

declare(strict_types=1);

use AzGuard\Context\Strategies\ContextOnlyStrategy;
use AzGuard\Context\Strategies\DenyWithoutContextStrategy;
use AzGuard\Context\Strategies\GlobalPlusContextStrategy;
use AzGuard\Registry\Values\PermissionSet;

it('GlobalPlusContext returns only global when no context', function (): void {
    $result = (new GlobalPlusContextStrategy)->merge(PermissionSet::fromKeys(['app.posts.view']), null);

    expect($result->grants('app.posts.view'))->toBeTrue()
        ->and($result->grants('app.posts.edit'))->toBeFalse();
});

it('GlobalPlusContext merges global and context', function (): void {
    $result = (new GlobalPlusContextStrategy)->merge(
        PermissionSet::fromKeys(['app.posts.view']),
        PermissionSet::fromKeys(['app.posts.edit']),
    );

    expect($result->grants('app.posts.view'))->toBeTrue()
        ->and($result->grants('app.posts.edit'))->toBeTrue();
});

it('GlobalPlusContext propagates a wildcard context', function (): void {
    $result = (new GlobalPlusContextStrategy)->merge(
        PermissionSet::fromKeys(['app.posts.view']),
        PermissionSet::wildcard(),
    );

    expect($result->isWildcard())->toBeTrue();
});

it('ContextOnly ignores global and is empty without context', function (): void {
    $strategy = new ContextOnlyStrategy;

    expect($strategy->merge(PermissionSet::fromKeys(['app.posts.view']), null)->keys())->toBe([]);

    $result = $strategy->merge(
        PermissionSet::fromKeys(['app.posts.view']),
        PermissionSet::fromKeys(['app.posts.edit']),
    );

    expect($result->grants('app.posts.view'))->toBeFalse()
        ->and($result->grants('app.posts.edit'))->toBeTrue();
});

it('DenyWithoutContext denies without context and merges with it', function (): void {
    $strategy = new DenyWithoutContextStrategy;
    $global = PermissionSet::fromKeys(['app.posts.view']);

    expect($strategy->merge($global, null)->grants('app.posts.view'))->toBeFalse()
        ->and($strategy->merge($global, PermissionSet::fromKeys(['app.posts.edit']))->grants('app.posts.view'))->toBeTrue();
});
