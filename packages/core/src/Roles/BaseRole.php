<?php

namespace AzGuard\Roles;

use AzGuard\Contracts\RoleInterface;
use Illuminate\Support\Str;

abstract class BaseRole implements RoleInterface
{
    public function getName(): string
    {
        return (string) Str::of(class_basename(static::class))
            ->replaceLast('Role', '')
            ->snake()
            ->slug();
    }

    public function getLevel(): int
    {
        return 0;
    }

    abstract public function permissions(): array;
}
