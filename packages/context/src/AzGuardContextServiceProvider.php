<?php

declare(strict_types=1);

namespace AzGuard\Context;

use AzGuard\Context\Contracts\MergeStrategy;
use AzGuard\Context\Contracts\ResolvesContext;
use AzGuard\Context\Middleware\SetAuthorizationContext;
use AzGuard\Context\Strategies\GlobalPlusContextStrategy;
use AzGuard\Contracts\ContextGuard as ContextGuardContract;
use AzGuard\Contracts\PermissionLayer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Override;

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
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/az-guard-context.php',
            'az-guard-context',
        );

        // Scoped — живёт один request. Под Octane singleton протёк бы контекстом
        // одного тенанта в следующий запрос на том же воркере.
        $this->app->scoped(AuthorizationContextManager::class);

        // Стратегия — конфигурируется в az-guard-context.php
        $this->app->bind(MergeStrategy::class, function (Application $app): MergeStrategy {
            $class = config('az-guard-context.merge_strategy', GlobalPlusContextStrategy::class);

            return $app->make($class);
        });

        // Резолверы из конфига — тегируем для инжекции в middleware
        $resolvers = config('az-guard-context.resolvers', []);
        foreach ($resolvers as $resolverClass) {
            $this->app->tag($resolverClass, ResolvesContext::class);
        }

        $this->app->bind(SetAuthorizationContext::class, fn (Application $app): SetAuthorizationContext => new SetAuthorizationContext(
            manager: $app->make(AuthorizationContextManager::class),
            resolvers: $app->tagged(ResolvesContext::class),
        ));

        // ContextPermissionLayer — scoped: применяет merge-стратегию к глобальному
        // набору прав ПОСЛЕ агрегации всех источников в EffectivePermissionResolver,
        // поэтому ContextOnly/DenyWithoutContext могут ограничивать, а не только
        // добавлять. Держит scoped-менеджер — делит его per-request жизненный цикл.
        $this->app->scoped(PermissionLayer::class, fn (Application $app): ContextPermissionLayer => new ContextPermissionLayer(
            manager: $app->make(AuthorizationContextManager::class),
            strategy: $app->make(MergeStrategy::class),
        ));

        // ContextGuard — реализация core-контракта для one-off контекстных проверок
        // ($user->hasPermissionIn(...) / hasPermission(..., $context)).
        // Scoped — инжектит scoped-менеджер и резолвер, должен делить их инстансы.
        $this->app->scoped(ContextGuardContract::class, ContextGuard::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/az-guard-context.php' => config_path('az-guard-context.php'),
            ], 'azguard-context-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'azguard-context-migrations');
        }
    }
}
