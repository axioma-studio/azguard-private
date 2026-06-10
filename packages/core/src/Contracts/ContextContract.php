<?php

declare(strict_types=1);

namespace AzGuard\Contracts;

/**
 * Contract for objects that carry a permission-check context.
 *
 * Implement this interface (or use the {@see \AzGuard\Support\PermissionContext} DTO)
 * when you need to pass a one-off context to {@see \AzGuard\Concerns\HasPermissions::hasPermission()}.
 *
 * @see \AzGuard\Support\PermissionContext
 */
interface ContextContract
{
    /**
     * The morph-type of the scoping entity, e.g. 'workspace', 'project'.
     */
    public function getContextType(): string;

    /**
     * The primary-key of the scoping entity.
     */
    public function getContextId(): int|string;
}
