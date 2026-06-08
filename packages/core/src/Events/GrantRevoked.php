<?php

declare(strict_types=1);

namespace AzGuard\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие диспатчится после отзыва direct grant.
 */
final class GrantRevoked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Authenticatable $user,
        public readonly string          $permissionKey,
        public readonly string          $panelId,
    ) {}
}
