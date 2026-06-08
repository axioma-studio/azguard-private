<?php

declare(strict_types=1);

namespace AzGuard\Commands;

use AzGuard\Models\DirectGrant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Просмотр активных direct grants.
 *
 * Примеры:
 *   php artisan guard:grants                           # все активные
 *   php artisan guard:grants --user=1                  # конкретный пользователь
 *   php artisan guard:grants --user=1 --panel=app      # пользователь + панель
 *   php artisan guard:grants --panel=admin             # все пользователи, панель admin
 *   php artisan guard:grants --all                     # включая истекшие
 *   php artisan guard:grants --format=json
 */
class GrantsListCommand extends Command
{
    protected $signature = 'guard:grants
        {--user=    : ID пользователя (фильтр)}
        {--panel=   : ID панели (фильтр)}
        {--model=   : FQCN модели (по умолчанию — модель пользователя из auth config)}
        {--all      : Включить истекшие гранты}
        {--format=table : Формат вывода: table, json, csv}';

    protected $description = 'Показать прямые grants разрешений (direct grants)';

    public function handle(): int
    {
        $userId     = $this->option('user');
        $panelId    = $this->option('panel');
        $modelClass = $this->option('model')
            ?? config('auth.providers.users.model', 'App\\Models\\User');
        $includeAll = (bool) $this->option('all');
        $format     = (string) $this->option('format');

        $query = DirectGrant::query()
            ->where('model_type', $modelClass)
            ->orderBy('panel_id')
            ->orderBy('model_id');

        if ($userId !== null) {
            $query->where('model_id', $userId);
        }
        if ($panelId !== null) {
            $query->where('panel_id', $panelId);
        }
        if (! $includeAll) {
            $query->where(function ($q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
        }

        $grants = $query->get(['model_id', 'panel_id', 'permission_key', 'expires_at']);

        if ($grants->isEmpty()) {
            $this->warn('Грантов не найдено.');
            return self::SUCCESS;
        }

        $rows = $grants->map(fn ($g) => [
            'user_id'        => $g->model_id,
            'panel'          => $g->panel_id,
            'permission_key' => $g->permission_key,
            'expires_at'     => $g->expires_at ? $g->expires_at->toDateTimeString() : '—',
        ])->all();

        match ($format) {
            'json' => $this->line(json_encode(array_values($rows), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
            'csv'  => $this->outputCsv($rows),
            default => $this->table(['User ID', 'Панель', 'Permission Key', 'Expires At'], $rows),
        };

        if ($format === 'table') {
            $this->line('');
            $this->line('<fg=gray>Итого: ' . count($rows) . ' грант(ов)</>') ;
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function outputCsv(array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $this->line(implode(',', array_keys($rows[0])));
        foreach ($rows as $row) {
            $this->line(implode(',', array_map(
                fn ($v) => is_string($v) && str_contains($v, ',') ? "\"{$v}\"" : (string) $v,
                $row,
            )));
        }
    }
}
