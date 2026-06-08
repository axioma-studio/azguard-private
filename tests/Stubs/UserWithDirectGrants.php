<?php

namespace AzGuard\Tests\Stubs;

use AzGuard\Concerns\HasAzGuard;
use AzGuard\Concerns\HasDirectGrants;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * User-стаб с подключённым HasDirectGrants для тестов DirectGrant.
 */
class UserWithDirectGrants extends Authenticatable
{
    use HasAzGuard, HasDirectGrants;

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password'];
}
