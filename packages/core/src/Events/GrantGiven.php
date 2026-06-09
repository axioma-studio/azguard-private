<?php

declare(strict_types=1);

namespace AzGuard\Events;

use AzGuard\Models\DirectGrant;
use Illuminate\Contracts\Auth\Authenticatable;

final readonly class GrantGiven
{
    public function __construct(
        public Authenticatable $user,
        public string $permissionKey,
        public string $panelId,
        public DirectGrant $grant,
    ) {}
}
