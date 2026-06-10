<?php

declare(strict_types=1);

namespace AzGuard\Registry\Exceptions;

use RuntimeException;

/**
 * Ключ permission не зарегистрирован в PermissionCatalog.
 * Бросается при попытке записать orphan-ключ в БД или при строгой проверке.
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
