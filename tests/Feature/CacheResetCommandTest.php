<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;

it('guard:cache-reset flushes the configured store with --force', function (): void {
    // A named array-backed store so the command does not treat it as "disabled".
    config()->set('cache.stores.azguard_test', ['driver' => 'array']);
    config()->set('az-guard.cache.store', 'azguard_test');

    Cache::store('azguard_test')->put('guard:test-key', 'value', 600);
    expect(Cache::store('azguard_test')->has('guard:test-key'))->toBeTrue();

    $this->artisan('guard:cache-reset', ['--force' => true])
        ->expectsOutputToContain('Flushed')
        ->assertSuccessful();

    expect(Cache::store('azguard_test')->has('guard:test-key'))->toBeFalse();
});

it('guard:cache-reset is a no-op when cross-request caching is disabled (array)', function (): void {
    config()->set('az-guard.cache.store', 'array');

    $this->artisan('guard:cache-reset')
        ->expectsOutputToContain('nothing to flush')
        ->assertSuccessful();
});
