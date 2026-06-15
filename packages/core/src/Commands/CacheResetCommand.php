<?php

declare(strict_types=1);

namespace AzGuard\Commands;

use Exception;
use Illuminate\Console\Command;

class CacheResetCommand extends Command
{
    protected $signature = 'guard:cache-reset {--force : Skip the confirmation prompt}';

    protected $description = 'Flush the AzGuard permission cache store';

    public function handle(): int
    {
        $store = (string) config('az-guard.cache.store', 'array');

        if ($store === 'array') {
            $this->info('Cross-request caching is disabled (store=array); nothing to flush.');

            return self::SUCCESS;
        }

        // flush() clears the ENTIRE cache store, not just AzGuard keys — a generic
        // store cannot delete by prefix. Point az-guard.cache.store at a dedicated
        // store to keep this safe, or confirm that wiping the shared store is OK.
        if (! $this->option('force') && ! $this->confirm(
            "This flushes the ENTIRE '{$store}' cache store, not only AzGuard keys. Continue?",
        )) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        try {
            cache()->store($store)->flush();
            $this->info("Flushed the '{$store}' cache store.");
        } catch (Exception $e) {
            $this->error("Failed to flush cache: {$e->getMessage()}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
