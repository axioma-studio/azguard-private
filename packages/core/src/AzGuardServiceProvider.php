<?php

namespace AzGuard;

use AzGuard\Commands\MakeGuardPanelCommand;
use AzGuard\Commands\MakeGuardRoleCommand;
use Illuminate\Support\ServiceProvider;

class AzGuardServiceProvider extends ServiceProvider
{
    /**
     * Регистрация компонентов в контейнере.
     */
    public function register(): void
    {
        // 1. Регистрируем менеджер как синглтон
        $this->app->singleton(AzGuardManager::class, function ($app) {
            return new AzGuardManager();
        });

        // 2. Слияние конфигурации
        $this->mergeConfigFrom(
            __DIR__ . '/../config/az-guard.php',
            'az-guard'
        );

        // 3. Регистрация провайдеров панелей из конфига
        $this->registerPanelProviders();
    }

    /**
     * Загрузка ресурсов пакета.
     */
    public function boot(): void
    {
        // Публикация конфига
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/az-guard.php' => config_path('az-guard.php'),
            ], 'az-guard-config');

            // Регистрация команд
            $this->commands([
                MakeGuardPanelCommand::class,
                MakeGuardRoleCommand::class,
            ]);
        }
    }

    /**
     * Динамическая регистрация провайдеров панелей.
     */
    protected function registerPanelProviders(): void
    {
        // Получаем список классов панелей из конфига пользователя
        $providers = config('az-guard.panels', []);

        foreach ($providers as $provider) {
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }
}
