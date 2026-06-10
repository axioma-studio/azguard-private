<?php

declare(strict_types=1);

namespace AzGuard\Context;

use AzGuard\Contracts\PermissionContext;

/**
 * Value object: описывает контекст авторизации.
 *
 * Пример: пользователь работает с workspace #42 панели "app".
 *
 *   $ctx = new AuthorizationContext(
 *       panelId:     'app',
 *       contextType: 'workspace',
 *       contextId:   42,
 *   );
 *
 * Иммутабельный — изменения создают новый экземпляр (withPanel / withContext).
 */
final readonly class AuthorizationContext implements PermissionContext
{
    public function __construct(
        public readonly string $panelId,
        public readonly string $contextType,
        public readonly int|string $contextId,
    ) {}

    public function contextType(): string
    {
        return $this->contextType;
    }

    public function contextId(): int|string
    {
        return $this->contextId;
    }

    /**
     * Вернуть новый контекст с другим panelId.
     */
    public function withPanel(string $panelId): self
    {
        return new self($panelId, $this->contextType, $this->contextId);
    }

    /**
     * Вернуть новый контекст с другой сущностью.
     */
    public function withContext(string $contextType, int|string $contextId): self
    {
        return new self($this->panelId, $contextType, $contextId);
    }

    /**
     * Строковый ключ для кэша / логов.
     */
    public function cacheKey(): string
    {
        return "{$this->panelId}:{$this->contextType}:{$this->contextId}";
    }

    public function equals(self $other): bool
    {
        return $this->panelId === $other->panelId
            && $this->contextType === $other->contextType
            && $this->contextId === $other->contextId;
    }
}
