<?php

namespace AzGuard;

use AzGuard\Facades\AzGuard;
use AzGuard\Support\Panel;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

abstract class PanelProvider extends ServiceProvider
{
    /**
     * Конфигурация панели, которую определяет наследник.
     */
    abstract public function panel(Panel $panel): Panel;

    /**
     * Регистрация панели в менеджере при загрузке приложения.
     */
    public function register(): void
    {
        AzGuard::registerPanel(fn() => $this->panel(Panel::make()));
    }

    /**
     * Запуск магии автоматического обнаружения.
     */
    public function boot(): void
    {
        $panel = $this->panel(Panel::make());

        // Получаем метаданные класса через Reflection
        $reflection = new ReflectionClass($this);
        $basePath = dirname($reflection->getFileName());
        $baseNamespace = $reflection->getNamespaceName();

        // Регистрируем компоненты с учетом ID панели для исключения коллизий
        $this->registerPolicies(
            $basePath . '/Policies',
            $baseNamespace . '\\Policies',
            $panel->getId()
        );
    }

    /**
     * Автоматическое связывание политик с моделями.
     * Права внутри политик будут автоматически префиксироваться ID панели.
     */
    protected function registerPolicies(string $path, string $namespace, string $panelId): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (File::allFiles($path) as $file) {
            $class = $namespace . '\\' . str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

            // Находим модель (например, PostPolicy -> App\Models\Post)
            $modelName = Str::replaceLast('Policy', '', class_basename($class));
            $modelClass = "App\\Models\\" . $modelName;

            if (class_exists($modelClass)) {
                // Регистрируем политику в Laravel Gate
                Gate::policy($modelClass, $class);

                // Дополнительно: можно внедрить ID панели в политику через контейнер,
                // если твои политики поддерживают конструктор, либо полагаться на стабы.
            }
        }
    }
}
