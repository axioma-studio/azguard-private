<?php

declare(strict_types=1);

namespace AzGuard\Registry\Exceptions;

use AzGuard\Exceptions\AzGuardException;

/**
 * Catalog conflict: a single key is declared from two sources with different definitions.
 */
final class InvalidCatalogException extends AzGuardException
{
    public static function duplicateKey(string $key, string $panelId, string $source1, string $source2): self
    {
        return new self(
            "Duplicate permission key [{$key}] in panel [{$panelId}]: declared by [{$source1}] and [{$source2}].",
        );
    }
}
