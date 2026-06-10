<?php

declare(strict_types=1);

namespace AzGuard\Contracts;

interface RoleInterface
{
    public function getName(): string;

    public function getLevel(): int;

    public function permissions(): array;
}
