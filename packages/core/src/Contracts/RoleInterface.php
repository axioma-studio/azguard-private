<?php

declare(strict_types=1);

namespace AzGuard\Contracts;

interface RoleInterface
{
    public function getName(): string;

    public function getLevel(): int;

    /** @return list<string> */
    public function permissions(): array;
}
