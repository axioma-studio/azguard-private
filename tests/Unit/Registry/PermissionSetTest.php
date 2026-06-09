<?php

declare(strict_types=1);

use AzGuard\Registry\Values\PermissionSet;

describe('PermissionSet', function () {

    // ─── Construction ───────────────────────────────────────────────────────

    it('creates empty set', function () {
        $set = PermissionSet::empty();

        expect($set->isEmpty())->toBeTrue()
            ->and($set->count())->toBe(0)
            ->and($set->toArray())->toBe([]);
    });

    it('creates wildcard set', function () {
        $set = PermissionSet::wildcard();

        expect($set->isWildcard())->toBeTrue()
            ->and($set->isEmpty())->toBeFalse();
    });

    it('creates set from keys and deduplicates', function () {
        $set = PermissionSet::fromKeys(['app.posts.view', 'app.posts.edit', 'app.posts.view']);

        expect($set->count())->toBe(2)
            ->and($set->toArray())->toBe(['app.posts.view', 'app.posts.edit']);
    });

    // ─── contains ───────────────────────────────────────────────────────────

    it('contains returns true for exact match', function () {
        $set = PermissionSet::fromKeys(['app.posts.view']);

        expect($set->contains('app.posts.view'))->toBeTrue()
            ->and($set->contains('app.posts.edit'))->toBeFalse();
    });

    it('wildcard set contains any key', function () {
        $set = PermissionSet::wildcard();

        expect($set->contains('anything.at.all'))->toBeTrue();
    });

    // ─── matchesWildcard ─────────────────────────────────────────────────────

    it('matches wildcard pattern', function () {
        $set = PermissionSet::fromKeys(['app.documents.*']);

        expect($set->matchesWildcard('app.documents.view'))->toBeTrue()
            ->and($set->matchesWildcard('app.documents.edit'))->toBeTrue()
            ->and($set->matchesWildcard('app.posts.view'))->toBeFalse();
    });

    it('global wildcard set matches any key via matchesWildcard', function () {
        $set = PermissionSet::wildcard();

        expect($set->matchesWildcard('whatever'))->toBeTrue();
    });

    it('set without wildcard patterns returns false for non-matching key', function () {
        $set = PermissionSet::fromKeys(['app.posts.view']);

        expect($set->matchesWildcard('app.posts.view'))->toBeFalse(); // exact, not pattern
    });

    // ─── grants ──────────────────────────────────────────────────────────────

    it('grants returns true for exact key', function () {
        $set = PermissionSet::fromKeys(['app.posts.view']);

        expect($set->grants('app.posts.view'))->toBeTrue();
    });

    it('grants returns true for wildcard pattern match', function () {
        $set = PermissionSet::fromKeys(['app.posts.*']);

        expect($set->grants('app.posts.delete'))->toBeTrue();
    });

    it('grants returns false when key not covered', function () {
        $set = PermissionSet::fromKeys(['app.posts.view', 'app.tags.*']);

        expect($set->grants('app.comments.create'))->toBeFalse();
    });

    // ─── merge ───────────────────────────────────────────────────────────────

    it('merges two regular sets and deduplicates', function () {
        $a = PermissionSet::fromKeys(['app.posts.view', 'app.posts.edit']);
        $b = PermissionSet::fromKeys(['app.posts.edit', 'app.tags.view']);
        $merged = $a->merge($b);

        expect($merged->toArray())->toContain('app.posts.view')
            ->toContain('app.posts.edit')
            ->toContain('app.tags.view')
            ->and($merged->count())->toBe(3);
    });

    it('merging with wildcard yields wildcard', function () {
        $regular = PermissionSet::fromKeys(['app.posts.view']);
        $wild = PermissionSet::wildcard();

        expect($regular->merge($wild)->isWildcard())->toBeTrue()
            ->and($wild->merge($regular)->isWildcard())->toBeTrue();
    });

    it('merging two empty sets gives empty set', function () {
        $merged = PermissionSet::empty()->merge(PermissionSet::empty());

        expect($merged->isEmpty())->toBeTrue();
    });

    // ─── filter ──────────────────────────────────────────────────────────────

    it('filter keeps only matching keys', function () {
        $set = PermissionSet::fromKeys(['app.posts.view', 'app.posts.edit', 'app.tags.view']);
        $known = ['app.posts.view', 'app.tags.view'];

        $filtered = $set->filter(fn (string $k) => in_array($k, $known, true));

        expect($filtered->toArray())->toBe(['app.posts.view', 'app.tags.view']);
    });

    it('filter of wildcard set still returns filtered set', function () {
        // Wildcard '*' as a key: filter checks string '*', not expansion
        $set = PermissionSet::wildcard();
        $filtered = $set->filter(fn (string $k) => $k !== '*');

        expect($filtered->isEmpty())->toBeTrue()
            ->and($filtered->isWildcard())->toBeFalse();
    });
});
