<?php

declare(strict_types=1);

namespace AzGuard\Roles;

use AzGuard\Contracts\RoleInterface;
use Illuminate\Support\Str;
use Override;

abstract class BaseRole implements RoleInterface
{
    #[Override]
    public function getName(): string
    {
        return (string) Str::of(class_basename(static::class))
            ->replaceLast('Role', '')
            ->snake()
            ->slug();
    }

    #[Override]
    public function getLevel(): int
    {
        return 0;
    }

    /** @return list<string> */
    #[Override]
    abstract public function permissions(): array;
}
