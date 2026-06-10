<?php

declare(strict_types=1);

namespace AzGuard\Registry\Exceptions;

use RuntimeException;

/**
 * Конфликт в каталоге: один ключ объявлен из двух источников с разными definition.
 */
final class InvalidCatalogException extends RuntimeException
{
    public static function duplicateKey(string $key, string $panelId, string $source1, string $source2): self
    {
        return new self(
            "Duplicate permission key [{$key}] in panel [{$panelId}]: declared by [{$source1}] and [{$source2}].",
        );
    }
}
