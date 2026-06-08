<?php

declare(strict_types=1);

namespace AzGuard\Support;

use AzGuard\Registry\Resolver\EffectivePermissionResolver;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Thin adapter: изолирует зависимость пакета core от packages/context.
 *
 * Проверяет наличие пакета azguard/context через class_exists — таким образом
 * core НЕ имеет hard-зависимости от context и работает без него.
 *
 * Методы никогда не бросают исключений — возвращают false при отсутствии
 * пакета или при любой ошибке.
 */
final class AzGuardContextBridge
{
    private const CONTEXT_MANAGER   = 'AzGuard\\Context\\AuthorizationContextManager';
    private const CONTEXT_CLASS     = 'AzGuard\\Context\\AuthorizationContext';
    private const RESOLVER_CACHE    = 'AzGuard\\Registry\\Resolver\\PermissionResolverCache';

    /**
     * Одноразовая проверка с произвольным $context-объектом.
     *
     * $context должен иметь публичные поля contextType и contextId.
     * panelId берётся из $context->panelId если есть, иначе из $panelId.
     *
     * Не изменяет глобальный AuthorizationContextManager.
     *
     * @param  object  $context  duck-typed: {contextType: string, contextId: int|string, panelId?: string}
     */
    public static function checkWithContext(
        Authenticatable $user,
        string $permission,
        string $panelId,
        object $context,
    ): bool {
        if (! class_exists(self::CONTEXT_MANAGER)) {
            // Пакет context не установлен — fallback к глобальной проверке
            return app(EffectivePermissionResolver::class)
                ->forUser($user, $panelId)
                ->grants($permission);
        }

        try {
            $effectivePanelId = property_exists($context, 'panelId')
                ? $context->panelId
                : $panelId;

            $contextObj = app(self::CONTEXT_CLASS, [
                'panelId'     => $effectivePanelId,
                'contextType' => $context->contextType,
                'contextId'   => $context->contextId,
            ]);

            return self::resolveWithIsolatedContext($user, $permission, $effectivePanelId, $contextObj);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Одноразовая проверка с contextType + contextId.
     * Это основной метод, вызываемый из hasAzPermissionIn().
     */
    public static function checkInContext(
        Authenticatable $user,
        string $contextType,
        int|string $contextId,
        string $permission,
        string $panelId,
    ): bool {
        if (! class_exists(self::CONTEXT_MANAGER)) {
            return false;
        }

        try {
            $contextObj = app(self::CONTEXT_CLASS, [
                'panelId'     => $panelId,
                'contextType' => $contextType,
                'contextId'   => $contextId,
            ]);

            return self::resolveWithIsolatedContext($user, $permission, $panelId, $contextObj);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Временно устанавливает контекст, резолвит права, восстанавливает исходный контекст.
     * Сбрасывает request-кэш resolver-а для данного пользователя чтобы получить
     * свежий результат с новым контекстом, затем восстанавливает.
     *
     * @param  object  $contextObj  AuthorizationContext instance
     */
    private static function resolveWithIsolatedContext(
        Authenticatable $user,
        string $permission,
        string $panelId,
        object $contextObj,
    ): bool {
        /** @var object $manager */
        $manager  = app(self::CONTEXT_MANAGER);
        $resolver = app(EffectivePermissionResolver::class);

        // Сохраняем текущий контекст
        $previous = $manager->current($panelId);

        // Устанавливаем изолированный контекст
        $manager->set($contextObj);
        $resolver->forgetForUser($user, $panelId);

        try {
            $result = $resolver->forUser($user, $panelId)->grants($permission);
        } finally {
            // Всегда восстанавливаем исходное состояние
            if ($previous !== null) {
                $manager->set($previous);
            } else {
                $manager->clear($panelId);
            }
            $resolver->forgetForUser($user, $panelId);
        }

        return $result;
    }
}
