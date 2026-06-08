<?php

declare(strict_types=1);

namespace AzGuard\Commands;

use Illuminate\Console\Command;

class CacheResetCommand extends Command
{
    protected $signature = 'azguard:cache-reset';

    protected $description = 'Сбросить кэш разрешений AzGuard';

    public function handle(): int
    {
        $store = config('az-guard.cache.store', 'array');
        $key = config('az-guard.cache.key', 'azguard.permissions');

        if ($store === 'array') {
            $this->warn('Кэш AzGuard использует store "array" (in-memory) — сброс не требуется.');
            return self::SUCCESS;
        }

        // Сбрасываем через тег если возможно, иначе просто выводим инфо
        try {
            cache()->store($store)->flush();
            $this->info("Кэш AzGuard ({$store}) сброшен. Ключ-префикс: {$key}");
        } catch (\Exception $e) {
            $this->warn("Не удалось сбросить кэш: {$e->getMessage()}");
            $this->line("Выполните вручную: cache()->store('{$store}')->flush()");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
