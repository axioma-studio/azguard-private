<?php

declare(strict_types=1);

namespace AzGuard\Commands;

use AzGuard\Models\Role;
use AzGuard\Models\RolePermission;
use Illuminate\Console\Command;

/**
 * Управление DB-разрешениями роли (az_guard_role_permissions).
 *
 * Примеры:
 *   php artisan guard:role-permissions list editor
 *   php artisan guard:role-permissions list editor --panel=app
 *   php artisan guard:role-permissions add  editor app.documents.view --panel=app
 *   php artisan guard:role-permissions remove editor app.documents.view --panel=app
 *   php artisan guard:role-permissions sync editor --panel=app --keys="app.x.view,app.x.edit"
 */
class RolePermissionsCommand extends Command
{
    protected $signature = 'guard:role-permissions
        {action          : list | add | remove | sync}
        {role            : Имя роли или её ID}
        {permission_key? : Ключ разрешения (для add / remove)}
        {--panel=app     : ID панели}
        {--keys=         : Список ключей через запятую (для sync)}
        {--force         : Не запрашивать подтверждение для sync}';

    protected $description = 'Управление DB-разрешениями роли (role_permissions)';

    public function handle(): int
    {
        $action = $this->argument('action');
        $roleArg = $this->argument('role');
        $panelId = (string) $this->option('panel');

        $role = is_numeric($roleArg)
            ? Role::find((int) $roleArg)
            : Role::where('name', $roleArg)->first();

        if ($role === null) {
            $this->error("Роль [{$roleArg}] не найдена в БД.");
            return self::FAILURE;
        }

        return match ($action) {
            'list'   => $this->actionList($role, $panelId),
            'add'    => $this->actionAdd($role, $panelId),
            'remove' => $this->actionRemove($role, $panelId),
            'sync'   => $this->actionSync($role, $panelId),
            default  => $this->invalidAction($action),
        };
    }

    // -------------------------------------------------------------------------

    private function actionList(Role $role, string $panelId): int
    {
        $query = $role->dbPermissions()->where('panel_id', $panelId);
        $perms = $query->get(['permission_key', 'panel_id', 'created_at']);

        $this->line('');
        $this->info("DB-разрешения роли <comment>{$role->name}</comment> (панель: {$panelId}):");

        if ($perms->isEmpty()) {
            $this->warn('  Разрешений нет.');
            return self::SUCCESS;
        }

        $this->table(
            ['Permission Key', 'Panel', 'Назначено'],
            $perms->map(fn ($p) => [
                $p->permission_key,
                $p->panel_id,
                $p->created_at?->toDateTimeString() ?? '—',
            ])->all(),
        );

        $this->line('<fg=gray>Итого: ' . $perms->count() . ' запис(ей)</>');

        return self::SUCCESS;
    }

    private function actionAdd(Role $role, string $panelId): int
    {
        $key = $this->argument('permission_key');
        if ($key === null) {
            $this->error('Укажите permission_key.');
            return self::FAILURE;
        }

        $exists = $role->dbPermissions()
            ->where('permission_key', $key)
            ->where('panel_id', $panelId)
            ->exists();

        if ($exists) {
            $this->warn("Разрешение [{$key}] уже есть у роли [{$role->name}] (панель: {$panelId}).");
            return self::SUCCESS;
        }

        RolePermission::create([
            'role_id'        => $role->id,
            'permission_key' => $key,
            'panel_id'       => $panelId,
        ]);

        $this->info("Добавлено: [{$key}] → роль [{$role->name}] (панель: {$panelId}).");

        return self::SUCCESS;
    }

    private function actionRemove(Role $role, string $panelId): int
    {
        $key = $this->argument('permission_key');
        if ($key === null) {
            $this->error('Укажите permission_key.');
            return self::FAILURE;
        }

        $deleted = $role->dbPermissions()
            ->where('permission_key', $key)
            ->where('panel_id', $panelId)
            ->delete();

        if ($deleted === 0) {
            $this->warn("Разрешение [{$key}] не найдено у роли [{$role->name}] (панель: {$panelId}).");
            return self::SUCCESS;
        }

        $this->info("Удалено: [{$key}] у роли [{$role->name}] (панель: {$panелId}).");

        return self::SUCCESS;
    }

    private function actionSync(Role $role, string $panelId): int
    {
        $keysRaw = (string) $this->option('keys');
        if ($keysRaw === '') {
            $this->error('Для sync укажите --keys="key1,key2,..."');
            return self::FAILURE;
        }

        $newKeys = array_filter(array_map('trim', explode(',', $keysRaw)));

        $existing = $role->dbPermissions()
            ->where('panel_id', $panelId)
            ->pluck('permission_key')
            ->all();

        $toAdd    = array_diff($newKeys, $existing);
        $toRemove = array_diff($existing, $newKeys);

        if (empty($toAdd) && empty($toRemove)) {
            $this->info('Разрешения уже синхронизированы — изменений нет.');
            return self::SUCCESS;
        }

        $this->line('');
        if (! empty($toAdd)) {
            $this->line('<fg=green>+ Будет добавлено:</>  ' . implode(', ', $toAdd));
        }
        if (! empty($toRemove)) {
            $this->line('<fg=red>- Будет удалено:</> ' . implode(', ', $toRemove));
        }
        $this->line('');

        if (! $this->option('force') && ! $this->confirm('Применить изменения?')) {
            $this->line('Отменено.');
            return self::SUCCESS;
        }

        // Добавляем новые
        foreach ($toAdd as $key) {
            RolePermission::create([
                'role_id'        => $role->id,
                'permission_key' => $key,
                'panel_id'       => $panelId,
            ]);
        }

        // Удаляем лишние
        if (! empty($toRemove)) {
            $role->dbPermissions()
                ->where('panel_id', $panelId)
                ->whereIn('permission_key', $toRemove)
                ->delete();
        }

        $this->info(sprintf(
            'Sync завершён: +%d добавлено, -%d удалено (роль: %s, панель: %s).',
            count($toAdd),
            count($toRemove),
            $role->name,
            $panelId,
        ));

        return self::SUCCESS;
    }

    private function invalidAction(string $action): int
    {
        $this->error("Неизвестный action [{$action}]. Допустимые: list, add, remove, sync.");
        return self::FAILURE;
    }
}
