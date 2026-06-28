<?php

declare(strict_types=1);

namespace AzGuard\Contracts;

use UnitEnum;

interface RoleInterface
{
    public function getName(): string;

    public function getLevel(): int;

    /**
     * Permissions granted by this role.
     *
     * Return enum cases (preferred — refactor-safe, scoped to their panel
     * automatically) or already-resolved panel-prefixed string keys. Return
     * `['*']` for a super-admin role that bypasses every check.
     *
     * @return list<UnitEnum|string>
     */
    public function permissions(): array;
}
