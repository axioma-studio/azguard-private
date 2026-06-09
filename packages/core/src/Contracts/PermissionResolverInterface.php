<?php

declare(strict_types=1);

namespace AzGuard\Contracts;

use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;

interface PermissionResolverInterface
{
    public function forUser(Authenticatable $user, string $panelId): PermissionSet;

    public function forgetForUser(Authenticatable $user, string $panelId): void;
}
