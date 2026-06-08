<?php

declare(strict_types=1);

namespace AzGuard\Context\Contracts;

/**
 * Контракт контекста авторизации.
 *
 * Контекст идентифицирует «пространство» (workspace, site, tenant),
 * в рамках которого проверяются права пользователя.
 */
interface AuthorizationContextInterface
{
    /**
     * Уникальный строковый идентификатор контекста.
     * Пример: "workspace:42", "site:production", "tenant:acme".
     */
    public function contextId(): string;

    /**
     * Тип контекста (workspace, site, tenant, ...).
     */
    public function contextType(): string;
}
