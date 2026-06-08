<?php

declare(strict_types=1);

namespace AzGuard\Registry\Contracts;

/**
 * Метаданные permission для UI, CLI, TypeScript-генерации.
 * Иммутабельный value object.
 */
interface PermissionMeta
{
    public function label(): ?string;

    public function description(): ?string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
