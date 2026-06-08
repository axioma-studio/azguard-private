<?php

declare(strict_types=1);

namespace AzGuard\Commands;

use AzGuard\Models\DirectGrant;
use Illuminate\Console\Command;

/**
 * Artisan-команда для удаления истёкших direct grants.
 *
 * Пригодна для подключения через Laravel Scheduler:
 *
 *   // bootstrap/app.php или Console/Kernel.php
 *   Schedule::command('az-guard:prune-grants')->daily();
 *
 * @example
 *   php artisan az-guard:prune-grants
 *   php artisan az-guard:prune-grants --panel=app
 */
final class PruneGrantsCommand extends Command
{
    protected $signature = 'az-guard:prune-grants
        {--panel= : Очистить только указанную панель}';

    protected $description = 'Удалить все истёкшие direct grants';

    public function handle(): int
    {
        $panel = $this->option('panel');

        $query = DirectGrant::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now());

        if ($panel !== null && $panel !== '') {
            $query->where('panel_id', $panel);
        }

        $deleted = $query->delete();

        $suffix = $panel ? " (panel: {$panel})" : '';
        $this->info("✅ Удалено {$deleted} истёкших grant(s){$suffix}.");

        return self::SUCCESS;
    }
}
