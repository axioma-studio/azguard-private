<?php

declare(strict_types=1);

namespace AzGuard\Events;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * @property string $permissionKey '*' если revokeAll()
 */
final readonly class GrantRevoked
{
    public function __construct(
        public Authenticatable $user,
        public string $permissionKey,
        public string $panelId,
    ) {}
}
