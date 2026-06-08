<?php

declare(strict_types=1);

namespace AzGuard\Events;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие диспатчится после выдачи direct grant пользователю.
 *
 * Пример подписки:
 *
 *   Event::listen(GrantGiven::class, function (GrantGiven $event): void {
 *       Log::info("Grant [{$event->permissionKey}] выдан user #{$event->user->getAuthIdentifier()}");
 *   });
 */
final class GrantGiven
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Authenticatable  $user,
        public readonly string           $permissionKey,
        public readonly string           $panelId,
        public readonly ?CarbonImmutable $expiresAt,
    ) {}
}
