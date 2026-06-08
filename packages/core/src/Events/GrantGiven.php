<?php

declare(strict_types=1);

namespace AzGuard\Events;

use AzGuard\Models\DirectGrant;
use Illuminate\Contracts\Auth\Authenticatable;

final class GrantGiven
{
    public function __construct(
        public readonly Authenticatable $user,
        public readonly string $permissionKey,
        public readonly string $panelId,
        public readonly DirectGrant $grant,
    ) {}
}
