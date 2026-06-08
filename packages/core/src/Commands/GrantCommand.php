<?php

declare(strict_types=1);

namespace AzGuard\Commands;

use AzGuard\Models\DirectGrant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Выдать пользователю прямой grant на разрешение.
 *
 * Примеры:
 *   php artisan guard:grant 1 app.documents.export --panel=app
 *   php artisan guard:grant 1 app.documents.export --panel=app --ttl=3600
 *   php artisan guard:grant 1 "*" --panel=app --ttl=86400
 */
class GrantCommand extends Command
{
    protected $signature = 'guard:grant
        {user_id        : ID пользователя (или модели)}
        {permission_key : Ключ разрешения (или * для wildcard)}
        {--panel=app    : ID панели}
        {--model=       : FQCN модели (по умолчанию — модель пользователя из auth config)}
        {--ttl=         : Время жизни гранта в секундах (0 / не указан = бессрочно)}';

    protected $description = 'Выдать прямой grant разрешения пользователю (без роли)';

    public function handle(): int
    {
        $userId        = $this->argument('user_id');
        $permissionKey = $this->argument('permission_key');
        $panelId       = (string) $this->option('panel');
        $modelClass    = $this->option('model')
            ?? config('auth.providers.users.model', 'App\\Models\\User');
        $ttl           = $this->option('ttl');

        if (! class_exists($modelClass)) {
            $this->error("Класс модели [{$modelClass}] не найден.");
            return self::FAILURE;
        }

        $expiresAt = null;
        if ($ttl !== null && (int) $ttl > 0) {
            $expiresAt = Carbon::now()->addSeconds((int) $ttl);
        }

        $table = config('az-guard.table_names.direct_grants', 'az_guard_direct_grants');

        $existing = DirectGrant::on()->from($table)
            ->where('model_type', $modelClass)
            ->where('model_id', $userId)
            ->where('permission_key', $permissionKey)
            ->where('panel_id', $panelId)
            ->first();

        if ($existing) {
            $existing->expires_at = $expiresAt;
            $existing->save();

            $this->info("Grant обновлён: [{$permissionKey}] → user #{$userId} (панель: {$panelId})" . ($expiresAt ? " до {$expiresAt}" : ' (бессрочно)') . '.');
        } else {
            DirectGrant::create([
                'model_type'     => $modelClass,
                'model_id'       => $userId,
                'permission_key' => $permissionKey,
                'panel_id'       => $panelId,
                'expires_at'     => $expiresAt,
            ]);

            $this->info("Grant выдан: [{$permissionKey}] → user #{$userId} (панель: {$panelId})" . ($expiresAt ? " до {$expiresAt}" : ' (бессрочно)') . '.');
        }

        return self::SUCCESS;
    }
}
