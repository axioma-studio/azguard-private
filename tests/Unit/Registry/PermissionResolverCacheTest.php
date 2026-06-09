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

    it('remembers result for same key', function () {
        $cache = new PermissionResolverCache;
        $calls = 0;

        $set = $cache->rememberForRequest('azguard.perms.1.app', function () use (&$calls): PermissionSet {
            $calls++;

            return PermissionSet::fromKeys(['app.posts.view']);
        });

        // Second call — must NOT invoke callback again
        $set2 = $cache->rememberForRequest('azguard.perms.1.app', function () use (&$calls): PermissionSet {
            $calls++;

            return PermissionSet::fromKeys(['different']);
        });

        expect($calls)->toBe(1)
            ->and($set2->toArray())->toBe(['app.posts.view']);
    });

    it('stores separate entries for different keys', function () {
        $cache = new PermissionResolverCache;

        $setA = $cache->rememberForRequest('azguard.perms.1.app', fn () => PermissionSet::fromKeys(['app.posts.view']));
        $setB = $cache->rememberForRequest('azguard.perms.2.app', fn () => PermissionSet::fromKeys(['app.tags.view']));

        expect($setA->toArray())->toBe(['app.posts.view'])
            ->and($setB->toArray())->toBe(['app.tags.view']);
    });

    it('forgetAll clears entire request cache', function () {
        $cache = new PermissionResolverCache;
        $calls = 0;

        $cache->rememberForRequest('azguard.perms.1.app', function () use (&$calls): PermissionSet {
            $calls++;

            return PermissionSet::fromKeys(['app.posts.view']);
        });

        $cache->forgetAll();

        $cache->rememberForRequest('azguard.perms.1.app', function () use (&$calls): PermissionSet {
            $calls++;

            return PermissionSet::fromKeys(['app.posts.view']);
        });

        expect($calls)->toBe(2);
    });

    it('forgetForUser removes entries matching user+panel prefix', function () {
        $cache = new PermissionResolverCache;
        $calls = 0;

        $cache->rememberForRequest('azguard.perms.1.app', fn () => PermissionSet::fromKeys(['app.posts.view']));
        $cache->rememberForRequest('azguard.perms.1.admin', fn () => PermissionSet::fromKeys(['admin.users.view']));
        $cache->rememberForRequest('azguard.perms.2.app', fn () => PermissionSet::fromKeys(['app.tags.view']));

        // Забываем user=1, panel=app
        $cache->forgetForUser(1, 'app');

        // user=1 app — пересчитывается
        $cache->rememberForRequest('azguard.perms.1.app', function () use (&$calls): PermissionSet {
            $calls++;

            return PermissionSet::fromKeys(['app.posts.view']);
        });

        // user=1 admin — остался в кэше, callback НЕ вызывается
        $cache->rememberForRequest('azguard.perms.1.admin', function () use (&$calls): PermissionSet {
            $calls++;

            return PermissionSet::fromKeys(['admin.users.view']);
        });

        expect($calls)->toBe(1);
    });
});
