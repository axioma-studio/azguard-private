<?php

declare(strict_types=1);

namespace AzGuard\Context;

use AzGuard\Context\Contracts\AuthorizationContextInterface;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

/**
 * Singleton-менеджер текущего контекста авторизации.
 *
 * Отвечает за:
 *  1. Хранение текущего контекста на время запроса (set/get/clear).
 *  2. Загрузку PermissionSet для пользователя в конкретном контексте.
 *
 * Установка контекста:
 *   app(AuthorizationContextManager::class)->setContext(
 *       AuthorizationContext::make('workspace', '42')
 *   );
 *
 * Обычно вызывается из SetAuthorizationContext middleware.
 */
final class AuthorizationContextManager
{
    private ?AuthorizationContextInterface $current = null;

    public function setContext(AuthorizationContextInterface $context): void
    {
        $this->current = $context;
    }

    public function getContext(): ?AuthorizationContextInterface
    {
        return $this->current;
    }

    public function hasContext(): bool
    {
        return $this->current !== null;
    }

    public function clearContext(): void
    {
        $this->current = null;
    }

    /**
     * Загружает права пользователя в текущем контексте из az_guard_context_permissions.
     *
     * Таблица:
     *   az_guard_context_permissions
     *     model_type       string
     *     model_id         bigint
     *     context_type     string   (workspace, site, ...)
     *     context_id       string   ("workspace:42")
     *     panel_id         string
     *     permission_key   string
     *
     * Возвращает PermissionSet::empty() если контекст не задан.
     */
    public function permissionsFor(
        Authenticatable $user,
        string $panelId,
        ?AuthorizationContextInterface $context = null,
    ): PermissionSet {
        $ctx = $context ?? $this->current;

        if ($ctx === null) {
            return PermissionSet::empty();
        }

        $table = config('az-guard-context.table', 'az_guard_context_permissions');

        $keys = DB::table($table)
            ->where('model_type', get_class($user))
            ->where('model_id', $user->getAuthIdentifier())
            ->where('context_id', $ctx->contextId())
            ->where('panel_id', $panelId)
            ->pluck('permission_key')
            ->all();

        if ($keys === []) {
            return PermissionSet::empty();
        }

        if (in_array('*', $keys, strict: true)) {
            return PermissionSet::wildcard();
        }

        return PermissionSet::fromKeys($keys);
    }
}
