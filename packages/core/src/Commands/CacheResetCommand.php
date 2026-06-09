<?php

declare(strict_types=1);

namespace AzGuard\Commands;

use Exception;
use Illuminate\Console\Command;

class CacheResetCommand extends Command
{
    protected $signature = 'azguard:cache-reset';

    protected $description = 'Flush the AzGuard permission cache';

    public function handle(): int
    {
        $store = config('az-guard.cache.store', 'array');
        $key = config('az-guard.cache.key', 'azguard.permissions');

        if ($store === 'array') {
            $this->warn('AzGuard cache uses the "array" store (in-memory) — nothing to flush.');

            return self::SUCCESS;
        }

        try {
            cache()->store($store)->flush();
            $this->info("AzGuard cache ({$store}) flushed. Key prefix: {$key}");
        } catch (Exception $e) {
            $this->warn("Failed to flush cache: {$e->getMessage()}");
            $this->line("Run manually: cache()->store('{$store}')->flush()");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
