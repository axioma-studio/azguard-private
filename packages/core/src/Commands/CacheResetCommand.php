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

        try {
            cache()->store($store)->flush();
            $this->info('AzGuard cache has been reset');
        } catch (Exception $e) {
            $this->error("Failed to flush cache: {$e->getMessage()}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
