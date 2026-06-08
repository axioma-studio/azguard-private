<?php

namespace AzGuard\Tests\Stubs;

use AzGuard\Concerns\HasDirectGrants;

/**
 * User-стаб с подключённым HasDirectGrants для тестов DirectGrant.
 * Наследует Stubs\User, чтобы factory() работал через Orchestra Testbench.
 */
class UserWithDirectGrants extends User
{
    use HasDirectGrants;
}
