<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;

it('azguard:cache-reset clears configured az-guard cache store', function (): void {
    Cache::store(config('az-guard.cache.store'))
        ->put('azguard:test-key', 'value', 600);

    expect(Cache::store(config('az-guard.cache.store'))->has('azguard:test-key'))->toBeTrue();

    $this->artisan('azguard:cache-reset')
        ->expectsOutputToContain('AzGuard cache has been reset')
        ->assertSuccessful();

    expect(Cache::store(config('az-guard.cache.store'))->has('azguard:test-key'))->toBeFalse();
});
