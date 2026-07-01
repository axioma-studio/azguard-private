<?php

declare(strict_types=1);

use AzGuard\Registry\Resolver\PermissionCache;
use AzGuard\Registry\Values\PermissionSet;

describe('PermissionCache', function () {

    it('generates canonical cache key embedding the current epoch', function () {
        $cache = new PermissionCache;

        expect($cache->keyFor(42, 'app'))
            ->toBe('azguard.perms.42.app.v1');

        expect($cache->keyFor('uuid-123', 'admin'))
            ->toBe('azguard.perms.uuid-123.admin.v1');
    });

    it('uses a fixed internal key prefix — az-guard.cache.key is not a knob (F38)', function () {
        $cache = new PermissionCache;

        // The removed dead knob must have no effect: Laravel's own cache.prefix
        // is the per-app isolation seam, not an AzGuard-specific config key.
        config(['az-guard.cache.key' => 'tenant7.acl']);

        expect($cache->keyFor(42, 'app'))->toBe('azguard.perms.42.app.v1')
            ->and($cache->keyFor(42, 'app', 'ctx-9'))->toBe('azguard.perms.42.app.v1.ctx-9');
    });

    it('remembers result for same user+panel', function () {
        $cache = new PermissionCache;
        $calls = 0;

        $set = $cache->rememberForRequest(1, 'app', function () use (&$calls): PermissionSet {
            $calls++;

            return PermissionSet::fromKeys(['app.posts.view']);
        });

        // Second call — must NOT invoke callback again
        $set2 = $cache->rememberForRequest(1, 'app', function () use (&$calls): PermissionSet {
            $calls++;

            return PermissionSet::fromKeys(['different']);
        });

        expect($calls)->toBe(1)
            ->and($set2->keys())->toBe(['app.posts.view']);
    });

    it('stores separate entries for different users', function () {
        $cache = new PermissionCache;

        $setA = $cache->rememberForRequest(1, 'app', fn () => PermissionSet::fromKeys(['app.posts.view']));
        $setB = $cache->rememberForRequest(2, 'app', fn () => PermissionSet::fromKeys(['app.tags.view']));

        expect($setA->keys())->toBe(['app.posts.view'])
            ->and($setB->keys())->toBe(['app.tags.view']);
    });

    it('forgetAll clears entire request cache', function () {
        $cache = new PermissionCache;
        $calls = 0;

        $cache->rememberForRequest(1, 'app', function () use (&$calls): PermissionSet {
            $calls++;

            return PermissionSet::fromKeys(['app.posts.view']);
        });

        $cache->forgetAll();

        $cache->rememberForRequest(1, 'app', function () use (&$calls): PermissionSet {
            $calls++;

            return PermissionSet::fromKeys(['app.posts.view']);
        });

        expect($calls)->toBe(2);
    });

    it('forgetRequestCache drops the in-process entry WITHOUT bumping the epoch', function () {
        // Persistent store so an epoch bump would be observable in keyFor().
        config()->set('cache.stores.azguard_test', ['driver' => 'array']);
        config()->set('az-guard.cache.store', 'azguard_test');

        $cache = new PermissionCache;
        $calls = 0;

        $cache->rememberForRequest(1, 'app', function () use (&$calls): PermissionSet {
            $calls++;

            return PermissionSet::fromKeys(['app.posts.view']);
        });

        expect($cache->keyFor(1, 'app'))->toBe('azguard.perms.1.app.v1');

        $cache->forgetRequestCache(1, 'app');

        // Epoch must NOT advance — the durable cross-request cache stays intact.
        expect($cache->keyFor(1, 'app'))->toBe('azguard.perms.1.app.v1');
    });

    it('forgetRequestCache forces an in-process recompute of that user+panel', function () {
        $cache = new PermissionCache;
        $calls = 0;

        $cache->rememberForRequest(1, 'app', function () use (&$calls): PermissionSet {
            $calls++;

            return PermissionSet::fromKeys(['app.posts.view']);
        });

        $cache->forgetRequestCache(1, 'app');

        $cache->rememberForRequest(1, 'app', function () use (&$calls): PermissionSet {
            $calls++;

            return PermissionSet::fromKeys(['app.posts.view']);
        });

        expect($calls)->toBe(2);
    });

    it('forgetForUser removes only that user+panel entry', function () {
        $cache = new PermissionCache;
        $calls = 0;

        $cache->rememberForRequest(1, 'app', fn () => PermissionSet::fromKeys(['app.posts.view']));
        $cache->rememberForRequest(1, 'admin', fn () => PermissionSet::fromKeys(['admin.users.view']));
        $cache->rememberForRequest(2, 'app', fn () => PermissionSet::fromKeys(['app.tags.view']));

        $cache->forgetForUser(1, 'app');

        // user=1 app — recomputed
        $cache->rememberForRequest(1, 'app', function () use (&$calls): PermissionSet {
            $calls++;

            return PermissionSet::fromKeys(['app.posts.view']);
        });

        // user=1 admin — still cached, callback NOT called
        $cache->rememberForRequest(1, 'admin', function () use (&$calls): PermissionSet {
            $calls++;

            return PermissionSet::fromKeys(['admin.users.view']);
        });

        expect($calls)->toBe(1);
    });
});
