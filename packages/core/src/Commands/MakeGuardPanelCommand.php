<?php

namespace AzGuard\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeGuardPanelCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'make:guard-panel';

    /**
     * @var string
     */
    protected $description = 'Создает новую изолированную структуру панели управления доступом';

    public function handle(): void
    {
        $basePathInput = trim($this->ask('Укажите путь для создания (например, app/Guards или Modules/Blog/Guards)', 'app/Guards'), '/');
        $panelName = $this->ask('Назовите панель (например, Admin)');

        $targetDir = base_path($basePathInput . '/' . $panelName);

        // Исправленная логика Namespace: заменяем только начальный "app"
        $namespacePath = preg_replace('/^app\b/i', 'App', $basePathInput);
        $baseNamespace = str_replace('/', '\\', $namespacePath) . '\\' . $panelName;

        if (File::exists($targetDir)) {
            $this->error("Ошибка: Панель по пути [{$targetDir}] уже существует!");
            return;
        }

        $roleName = $this->ask('Название первой роли', 'Admin');
        $permissionName = $this->ask('Название маппинга разрешений (ресурса)', 'Post');

        $subDirs = ['Roles', 'Policies', 'Permissions', 'Scopes', 'Plugins'];
        foreach ($subDirs as $dir) {
            File::makeDirectory("{$targetDir}/{$dir}", 0755, true);
        }

        // Универсальные переменные для всех стабов
        $replacements = [
            'namespace' => $baseNamespace,
            'panel'     => $panelName,
            'name'      => $roleName,           // Для Role
            'resource'  => $permissionName,     // Для Policy/Permission
            'nameLower' => Str::lower($roleName),
            'resLower'  => Str::lower($permissionName),
        ];

        $this->generateFile($targetDir, "{$panelName}GuardPanelProvider.php", 'provider', $replacements);
        $this->generateFile("{$targetDir}/Roles", "{$roleName}Role.php", 'role', $replacements);
        $this->generateFile("{$targetDir}/Permissions", "{$permissionName}Permission.php", 'permission', $replacements);
        $this->generateFile("{$targetDir}/Policies", "{$permissionName}Policy.php", 'policy', $replacements);

        File::put("{$targetDir}/Scopes/.gitkeep", "");
        File::put("{$targetDir}/Plugins/.gitkeep", "");

        $this->info("✅ Панель [{$panelName}] успешно создана!");
    }

    protected function generateFile(string $path, string $filename, string $stubName, array $replacements): void
    {
        $stubPath = __DIR__ . "/../../stubs/panel/{$stubName}.stub";
        if (!File::exists($stubPath)) return;

        $content = File::get($stubPath);

        foreach ($replacements as $key => $value) {
            $content = str_replace("{{ $key }}", $value, $content);
        }

        File::put("{$path}/{$filename}", $content);
    }
}
