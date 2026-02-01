<?php

namespace AzGuard\Tests\Stubs\Roles;

use AzGuard\Roles\BaseRole;

class ManagerRole extends BaseRole
{
    public function permissions(): array
    {
        return ['edit-users'];
    }
}
