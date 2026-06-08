<?php

declare(strict_types=1);

namespace AzGuard\Commands;

use AzGuard\Models\DirectGrant;
use Illuminate\Console\Command;

/**
 * Отозвать прямой grant (или все гранты) у пользователя.
 *
 * Примеры:
 *   php artisan guard:revoke 1 app.documents.export --panel=app
 *   php artisan guard:revoke 1 --all --panel=app
 *   php artisan guard:revoke 1 --all
 */
class RevokeCommand extends Command
{
    protected $signature = 'guard:revoke
        {user_id        : ID пользователя (или модели)}
        {permission_key? : Ключ разрешения (не нужен при --all)}
        {--panel=app    : ID панели}
        {--all          : Отозвать все гранты пользователя (в указанной панели или во всех)}
        {--model=       : FQCN модели (по умолчанию — модель пользователя из auth config)}
        {--force        : Не запрашивать подтверждение}';

    protected $description = 'Отозвать прямой grant разрешения у пользователя';

    public function handle(): int
    {
        $userId        = $this->argument('user_id');
        $permissionKey = $this->argument('permission_key');
        $panelId       = (string) $this->option('panel');
        $revokeAll     = (bool) $this->option('all');
        $force         = (bool) $this->option('force');
        $modelClass    = $this->option('model')
            ?? config('auth.providers.users.model', 'App\\Models\\User');

        if (! $revokeAll && $permissionKey === null) {
            $this->error('Укажите permission_key или используйте --all.');
            return self::FAILURE;
        }

        if (! class_exists($modelClass)) {
            $this->error("Класс модели [{$modelClass}] не найден.");
            return self::FAILURE;
        }

        $query = DirectGrant::where('model_type', $modelClass)
            ->where('model_id', $userId);

        if ($revokeAll) {
            // --all без --panel=* означает конкретную панель; можно пройти по всем
            if ($this->option('panel') !== 'app' || $panelId !== 'app') {
                $query->where('panel_id', $panelId);
            }
            $count = $query->count();
            $label = $revokeAll ? "все гранты (панель: {$panelId})" : "[{$permissionKey}] (панель: {$panelId})";
        } else {
            $query->where('permission_key', $permissionKey)
                  ->where('panel_id', $panelId);
            $count = $query->count();
            $label = "[{$permissionKey}] (панель: {$panelId})";
        }

        if ($count === 0) {
            $this->warn("Грантов не найдено: {$label} у user #{$userId}.");
            return self::SUCCESS;
        }

        if (! $force && ! $this->confirm("Удалить {$count} грант(ов) {$label} у user #{$userId}?")) {
            $this->line('Отменено.');
            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("Отозвано {$deleted} грант(ов): {$label} у user #{$userId}.");

        return self::SUCCESS;
    }
}
