<?php

namespace AzGuard\Contracts;

interface RoleInterface
{
    public function getName(): string;

    public function getLevel(): int;

    public function permissions(): array;
}
