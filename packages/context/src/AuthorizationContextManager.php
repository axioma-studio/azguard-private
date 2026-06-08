<?php

declare(strict_types=1);

namespace AzGuard\Context;

/**
 * Singleton: хранит активный AuthorizationContext для текущего request.
 *
 * Регистрируется как singleton в AzGuardContextServiceProvider.
 * Устанавливается middleware SetAuthorizationContext (или вручную).
 *
 * Пример:
 *   app(AuthorizationContextManager::class)->set(
 *       new AuthorizationContext('app', 'workspace', $workspaceId)
 *   );
 *
 *   $ctx = app(AuthorizationContextManager::class)->current('app');
 */
final class AuthorizationContextManager
{
    /** @var array<string, AuthorizationContext> panelId => context */
    private array $contexts = [];

    /**
     * Установить контекст для конкретной панели.
     */
    public function set(AuthorizationContext $context): void
    {
        $this->contexts[$context->panelId] = $context;
    }

    /**
     * Получить активный контекст для панели.
     * Возвращает null если контекст не установлен.
     */
    public function current(string $panelId): ?AuthorizationContext
    {
        return $this->contexts[$panelId] ?? null;
    }

    /**
     * Проверить, установлен ли контекст для панели.
     */
    public function has(string $panelId): bool
    {
        return isset($this->contexts[$panelId]);
    }

    /**
     * Сбросить контекст для панели (например, в tearDown тестов).
     */
    public function clear(string $panelId): void
    {
        unset($this->contexts[$panelId]);
    }

    /**
     * Сбросить все контексты.
     */
    public function clearAll(): void
    {
        $this->contexts = [];
    }
}
