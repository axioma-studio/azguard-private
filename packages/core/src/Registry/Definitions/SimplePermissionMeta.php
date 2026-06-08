<?php

declare(strict_types=1);

namespace AzGuard\Registry\Definitions;

use AzGuard\Registry\Contracts\PermissionMeta;

/**
 * Простая иммутабельная реализация PermissionMeta.
 */
final class SimplePermissionMeta implements PermissionMeta
{
    public function __construct(
        private readonly ?string $label = null,
        private readonly ?string $description = null,
    ) {}

    public function label(): ?string
    {
        return $this->label;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'label'       => $this->label,
            'description' => $this->description,
        ];
    }
}
