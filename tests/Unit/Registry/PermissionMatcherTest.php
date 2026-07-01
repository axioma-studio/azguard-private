<?php

declare(strict_types=1);

use AzGuard\Contracts\PermissionMatcher;
use AzGuard\Registry\Matching\HierarchicalPermissionMatcher;
use AzGuard\Registry\Matching\WildcardPermissionMatcher;
use AzGuard\Registry\Values\PermissionSet;

// F22: default (legacy) grammar — '*' crosses dot boundaries. Unchanged in 0.3.0.
it('keeps the default wildcard grammar crossing dot boundaries', function () {
    $matcher = new WildcardPermissionMatcher;

    expect($matcher->matches('a.*', 'a.b'))->toBeTrue()
        ->and($matcher->matches('a.*', 'a.b.c'))->toBeTrue()      // crosses dots (legacy)
        ->and($matcher->matches('a.b.*', 'a.b.c'))->toBeTrue()
        ->and($matcher->matches('a.*', 'x.b'))->toBeFalse();
});

// F22: hierarchical grammar — '*' is one segment, '**' is recursive.
it('matches one segment with * and recurses with ** in the hierarchical grammar', function () {
    $matcher = new HierarchicalPermissionMatcher;

    expect($matcher->matches('a.*', 'a.b'))->toBeTrue()
        ->and($matcher->matches('a.*', 'a.b.c'))->toBeFalse()     // does NOT cross dots
        ->and($matcher->matches('a.**', 'a.b'))->toBeTrue()
        ->and($matcher->matches('a.**', 'a.b.c'))->toBeTrue()
        ->and($matcher->matches('a.**', 'a.b.c.d'))->toBeTrue()
        ->and($matcher->matches('a.b', 'a.b'))->toBeTrue()
        ->and($matcher->matches('a.*', 'x.b'))->toBeFalse();
});

// F21: compiled pattern is memoized — not recompiled per key.
it('memoizes the compiled pattern instead of recompiling per key', function () {
    $matcher = new WildcardPermissionMatcher;

    $matcher->matches('a.*', 'a.b');
    $matcher->matches('a.*', 'a.c');
    $matcher->matches('a.*', 'a.d');

    $compiled = (new ReflectionProperty($matcher, 'compiled'))->getValue($matcher);

    expect($compiled)->toHaveCount(1)->toHaveKey('a.*');
});

// F21: PermissionSet routes wildcard matching through the config-swappable matcher.
it('lets a config-overridden matcher change PermissionSet wildcard matching', function () {
    // Default matcher: 'a.*' crosses dots.
    expect(PermissionSet::fromKeys(['a.*'])->grants('a.b.c'))->toBeTrue();

    config()->set('az-guard.matcher', HierarchicalPermissionMatcher::class);
    app()->forgetInstance(PermissionMatcher::class);

    expect(PermissionSet::fromKeys(['a.*'])->grants('a.b.c'))->toBeFalse()
        ->and(PermissionSet::fromKeys(['a.*'])->grants('a.b'))->toBeTrue()
        ->and(PermissionSet::fromKeys(['a.**'])->grants('a.b.c'))->toBeTrue();
});
