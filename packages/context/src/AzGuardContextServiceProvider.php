<?php

declare(strict_types=1);

namespace AzGuard\Context;

use AzGuard\Context\Contracts\ContextMergeStrategy;
use AzGuard\Context\Contracts\ResolvesContext;
use AzGuard\Context\Middleware\SetAuthorizationContext;
use AzGuard\Context\Strategies\GlobalPlusContextStrategy;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider для azguard/context.
 *
 * Публикует конфиг, регистрирует синглтон AuthorizationContextManager,
 * биндит дефолтную стратегию, регистрирует middleware.
 *
 * Конфиг az-guard-context.php:
 *   'merge_strategy' => GlobalPlusContextStrategy::class,
 *   'resolvers'      => [],   // FQCN классов ResolvesContext
 */
final class AzGuardContextServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/az-guard-context.php',
            'az-guard-context',
        );

        // Singleton менеджера — живёт весь request
        $this->app->singleton(AuthorizationContextManager::class);

        // Стратегия — конфигурируется в az-guard-context.php
        $this->app->bind(ContextMergeStrategy::class, function (Application $app): ContextMergeStrategy {
            $class = config('az-guard-context.merge_strategy', GlobalPlusContextStrategy::class);

            return $app->make($class);
        });

        // Резолверы из конфига — тегируем для инжекции в middleware
        $resolvers = config('az-guard-context.resolvers', []);
        foreach ($resolvers as $resolverClass) {
            $this->app->tag($resolverClass, ResolvesContext::class);
        }

        $this->app->bind(SetAuthorizationContext::class, function (Application $app): SetAuthorizationContext {
            return new SetAuthorizationContext(
                manager: $app->make(AuthorizationContextManager::class),
                resolvers: $app->tagged(ResolvesContext::class),
            );
        });

        // ContextualRoleGrantSource
        $this->app->bind(ContextualRoleGrantSource::class, function (Application $app): ContextualRoleGrantSource {
            return new ContextualRoleGrantSource(
                manager: $app->make(AuthorizationContextManager::class),
                strategy: $app->make(ContextMergeStrategy::class),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/az-guard-context.php' => config_path('az-guard-context.php'),
            ], 'azguard-context-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'azguard-context-migrations');
        }
    }
}
