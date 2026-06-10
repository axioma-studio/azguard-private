<?php

declare(strict_types=1);

namespace AzGuard\Support;

use AzGuard\Contracts\ContextContract;

/**
 * Immutable value object that carries a permission-check context.
 *
 * Use this DTO instead of passing anonymous objects or plain stdClass
 * to {@see \AzGuard\Concerns\HasPermissions::hasPermission()}.
 *
 * Example:
 *
 *   $user->hasPermission(
 *       'app.projects.edit',
 *       context: PermissionContext::for('workspace', $workspace->id),
 *   );
 */
final readonly class PermissionContext implements ContextContract
{
    public function __construct(
        private string $contextType,
        private int|string $contextId,
    ) {}

    /**
     * Named constructor — more readable at call sites.
     */
    public static function for(string $contextType, int|string $contextId): self
    {
        return new self($contextType, $contextId);
    }

    public function getContextType(): string
    {
        return $this->contextType;
    }

    public function getContextId(): int|string
    {
        return $this->contextId;
    }
}
