<?php

namespace AzGuard\Commands;

use AzGuard\Facades\AzGuard;
use AzGuard\Support\Panel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class MakeGuardRoleCommand extends Command
{
    protected $signature = 'make:guard-role';
    protected $description = 'Создать новую роль для конкретной панели';

    public function handle(): void
    {
        $panels = AzGuard::getPanels();

        if (empty($panels)) {
            $this->warn('Зарегистрированные панели не найдены.');
            $this->line('1. Создайте панель: <info>php artisan make:guard-panel</info>');
            $this->line('2. Зарегистрируйте её в <info>config/az-guard.php</info>');
            return;
        }

        // Выбор панели из списка зарегистрированных
        $panelIds = array_keys($panels);
        $selectedId = $this->choice('Для какой панели создать роль?', $panelIds, 0);

        /** @var Panel $panel */
        $panel = $panels[$selectedId];

        // Поиск провайдера для определения путей через Reflection
        $providerClass = $this->getProviderClassById($selectedId);

        if (!$providerClass) {
            $this->error("Провайдер для панели [{$selectedId}] не найден в контейнере.");
            return;
        }

        $roleName = $this->ask('Название роли (например, Editor)');
        $roleClass = Str::studly($roleName);

        $reflection = new ReflectionClass($providerClass);
        $panelPath = dirname($reflection->getFileName());
        $panelNamespace = $reflection->getNamespaceName();

        $targetPath = "{$panelPath}/Roles/{$roleClass}Role.php";

        if (File::exists($targetPath)) {
            $this->error("Роль [{$roleClass}] уже существует в этой панели!");
            return;
        }

        File::ensureDirectoryExists("{$panelPath}/Roles");

        // Формируем контент с ПРЕФИКСОМ панели
        $this->generateFile($targetPath, [
            'namespace' => $panelNamespace,
            'name'      => $roleClass,
            // Вот здесь решается проблема коллизий: admin.post / app.post
            'resLower'  => $selectedId . '.base',
        ]);

        $this->info("✅ Роль успешно создана: {$targetPath}");
    }

    protected function getProviderClassById(string $id): ?string
    {
        foreach (app()->getLoadedProviders() as $providerClass => $bool) {
            if (is_subclass_of($providerClass, \AzGuard\PanelProvider::class)) {
                $instance = app()->getProvider($providerClass);
                // Проверяем ID панели, который возвращает провайдер
                if ($instance->panel(Panel::make())->getId() === $id) {
                    return $providerClass;
                }
            }
        }
        return null;
    }

    protected function generateFile(string $path, array $replacements): void
    {
        $stubPath = __DIR__ . '/../../stubs/panel/role.stub';
        $content = File::get($stubPath);

        foreach ($replacements as $key => $value) {
            $content = str_replace("{{ $key }}", $value, $content);
        }

        File::put($path, $content);
    }
}
