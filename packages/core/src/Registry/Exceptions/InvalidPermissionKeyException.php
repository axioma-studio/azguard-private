<?php

declare(strict_types=1);

namespace AzGuard\Registry\Exceptions;

use RuntimeException;

/**
 * The permission key is not registered in the PermissionCatalog.
 * Thrown when attempting to persist an orphan key to the database or during strict validation.
 */
final class InvalidPermissionKeyException extends RuntimeException
{
    public static function forKey(string $key, string $panelId): self
    {
        return new self(
            "Permission key [{$key}] is not registered in the catalog for panel [{$panelId}].",
        );
    }
}
