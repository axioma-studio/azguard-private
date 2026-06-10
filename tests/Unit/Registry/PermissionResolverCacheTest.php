<?php

declare(strict_types=1);

use AzGuard\Registry\Resolver\PermissionResolverCache;
use AzGuard\Registry\Values\PermissionSet;

describe('PermissionResolverCache', function () {

    it('generates canonical cache key', function () {
        expect(PermissionResolverCache::keyFor(42, 'app'))
            ->toBe('azguard.perms.42.app');

        expect(PermissionResolverCache::keyFor('uuid-123', 'admin'))
            ->toBe('azguard.perms.uuid-123.admin');
    });

    it('remembers result for same user+panel', function () {
        $cache = new PermissionResolverCache;
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
            ->and($set2->toArray())->toBe(['app.posts.view']);
    });

    it('stores separate entries for different users', function () {
        $cache = new PermissionResolverCache;

        $setA = $cache->rememberForRequest(1, 'app', fn () => PermissionSet::fromKeys(['app.posts.view']));
        $setB = $cache->rememberForRequest(2, 'app', fn () => PermissionSet::fromKeys(['app.tags.view']));

        expect($setA->toArray())->toBe(['app.posts.view'])
            ->and($setB->toArray())->toBe(['app.tags.view']);
    });

    it('forgetAll clears entire request cache', function () {
        $cache = new PermissionResolverCache;
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

    it('forgetForUser removes only that user+panel entry', function () {
        $cache = new PermissionResolverCache;
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
