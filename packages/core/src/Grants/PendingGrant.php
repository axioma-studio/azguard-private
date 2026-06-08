<?php

declare(strict_types=1);

namespace AzGuard\Grants;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Неизменяемый value object, описывающий ожидающую операцию grant'a.
 * Создаётся через GrantManager::for() и передаётся в GrantManager через ->save().
 */
final class PendingGrant
{
    public function __construct(
        public readonly Authenticatable $user,
        public readonly string          $panelId,
        public readonly string          $permissionKey,
        public readonly ?int            $ttlSeconds,
    ) {}

    public function expiresAt(): ?CarbonImmutable
    {
        if ($this->ttlSeconds === null || $this->ttlSeconds <= 0) {
            return null;
        }

        return CarbonImmutable::now()->addSeconds($this->ttlSeconds);
    }
}
