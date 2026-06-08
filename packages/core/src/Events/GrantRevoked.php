<?php

declare(strict_types=1);

namespace AzGuard\Events;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * @property string $permissionKey  '*' если revokeAll()
 */
final class GrantRevoked
{
    public function __construct(
        public readonly Authenticatable $user,
        public readonly string $permissionKey,
        public readonly string $panelId,
    ) {}
}
