<?php

declare(strict_types=1);

namespace AzGuard\Commands;

use AzGuard\Contracts\AzGuardManagerInterface;
use Illuminate\Console\Command;

/**
 * Команда azguard:sync-roles
 *
 * Синхронизирует PHP-классы ролей с таблицей ролей в БД.
 * Использует class_name, зарегистрированный через PanelProvider'ы.
 *
 * Опции:
 *   --panel=   Синхронизировать только панель с указанным ID
 *   --dry-run  Показать изменения без записи в БД
 */
final class SyncRolesCommand extends Command
{
    protected $signature = 'azguard:sync-roles
                            {--panel= : ID панели для фильтрации (опционально)}
                            {--dry-run : Показать изменения без записи в БД}';

    protected $description = 'Синхронизирует PHP-классы ролей с таблицей roles в БД';

    public function handle(AzGuardManagerInterface $manager): int
    {
        $panels = $manager->getPanels();
        $panelFilter = $this->option('panel');
        $isDryRun = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('[dry-run] Изменения не будут записаны в БД.');
        }

        if ($panels === []) {
            $this->warn('Панели AzGuard не зарегистрированы. Проверьте az-guard.panels в конфиге.');
            return self::SUCCESS;
        }

        /** @var class-string<\AzGuard\Models\Role> $roleModel */
        $roleModel = config('az-guard.models.role');

        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $rows = [];

        foreach ($panels as $panelId => $panel) {
            if (is_string($panelFilter) && $panelFilter !== '' && $panelFilter !== $panelId) {
                continue;
            }

            $basePath = $panel->getBasePath();
            $namespace = $panel->getNamespace();

            if ($basePath === '' || $namespace === '') {
                $this->warn("Панель [{$panelId}]: basePath или namespace не задан — пропускается.");
                continue;
            }

            $rolesPath = rtrim($basePath, '/') . '/Roles';

            if (! is_dir($rolesPath)) {
                continue;
            }

            $files = glob($rolesPath . '/*Role.php') ?: [];

            foreach ($files as $file) {
                $className = $namespace . '\\Roles\\' . basename($file, '.php');

                if (! class_exists($className)) {
                    continue;
                }

                $roleLogic = new $className;

                if (! method_exists($roleLogic, 'getName') || ! method_exists($roleLogic, 'getLevel')) {
                    continue;
                }

                $name = $roleLogic->getName();
                $level = $roleLogic->getLevel();

                $existing = $roleModel::query()->where('class_name', $className)->first();

                if ($existing !== null) {
                    if ($existing->name !== $name || $existing->level !== $level) {
                        $rows[] = [$panelId, $name, $level, $className, '<fg=yellow>updated</fg=yellow>'];
                        $updated++;
                        if (! $isDryRun) {
                            $existing->update(['name' => $name, 'level' => $level]);
                        }
                    } else {
                        $rows[] = [$panelId, $name, $level, $className, '<fg=gray>unchanged</fg=gray>'];
                        $unchanged++;
                    }
                } else {
                    $rows[] = [$panelId, $name, $level, $className, '<fg=green>created</fg=green>'];
                    $created++;
                    if (! $isDryRun) {
                        $roleModel::query()->create([
                            'name'       => $name,
                            'level'      => $level,
                            'class_name' => $className,
                        ]);
                    }
                }
            }
        }

        if ($rows !== []) {
            $this->table(
                headers: ['Панель', 'Имя', 'Уровень', 'Класс', 'Статус'],
                rows: $rows,
            );
        }

        $suffix = $isDryRun ? ' (dry-run)' : '';
        $this->info("Синхронизация завершена{$suffix}: создано={$created}, обновлено={$updated}, без изменений={$unchanged}");

        return self::SUCCESS;
    }
}
