<?php

declare(strict_types=1);

namespace AzGuard\Context;

use AzGuard\Context\Contracts\AuthorizationContextInterface;

/**
 * Value object контекста авторизации.
 *
 * Иммутабельный. Создаётся приложением и передаётся в AuthorizationContextManager.
 *
 * Пример:
 *   $ctx = new AuthorizationContext('workspace', '42');
 *   $ctx->contextId();   // 'workspace:42'
 *   $ctx->contextType(); // 'workspace'
 */
final class AuthorizationContext implements AuthorizationContextInterface
{
    public function __construct(
        private readonly string $type,
        private readonly string $id,
    ) {}

    public function contextId(): string
    {
        return "{$this->type}:{$this->id}";
    }

    public function contextType(): string
    {
        return $this->type;
    }

    /**
     * Фабричный метод для удобства.
     */
    public static function make(string $type, string $id): self
    {
        return new self($type, $id);
    }
}
