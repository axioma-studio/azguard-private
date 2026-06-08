<?php

declare(strict_types=1);

namespace AzGuard\Context;

use AzGuard\Context\Contracts\MergeStrategy;
use AzGuard\Context\Sources\ContextualRoleGrantSource;
use AzGuard\Context\Strategies\GlobalPlusContextStrategy;
use AzGuard\Registry\Contracts\GrantSource;
use Illuminate\Support\ServiceProvider;

/**
 * Провайдер пакета azguard/context.
 *
 * Регистрирует:
 *  - AuthorizationContextManager (singleton)
 *  - ContextualRoleGrantSource как дополнительный GrantSource
 *  - MergeStrategy (по умолчанию GlobalPlusContextStrategy, переопределяется в config)
 *
 * Использование:
 *   composer require axioma-studio/azguard-context
 *   php artisan vendor:publish --tag=azguard-context-config
 */
final class AzGuardContextServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuthorizationContextManager::class);

        // Стратегия merge — переопределяется через config или binding в AppServiceProvider.
        $this->app->bind(MergeStrategy::class, function () {
            $strategyClass = config(
                'az-guard-context.merge_strategy',
                GlobalPlusContextStrategy::class,
            );

            return new $strategyClass();
        });

        // Регистрируем ContextualRoleGrantSource в контейнере tagged-коллекции.
        // EffectivePermissionResolver получает все GrantSource через tag 'azguard.grant_source'.
        $this->app->tag([ContextualRoleGrantSource::class], 'azguard.grant_source');
        $this->app->bind(ContextualRoleGrantSource::class, function ($app) {
            return new ContextualRoleGrantSource(
                $app->make(AuthorizationContextManager::class),
                $app->make(MergeStrategy::class),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/az-guard-context.php' => config_path('az-guard-context.php'),
            ], 'azguard-context-config');
        }
    }
}
