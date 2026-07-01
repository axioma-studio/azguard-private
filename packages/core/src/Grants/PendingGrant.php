<?php

declare(strict_types=1);

namespace AzGuard\Grants;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Immutable value object describing a pending grant operation.
 * Created via GrantManager::for() and passed back to GrantManager through ->save().
 */
final readonly class PendingGrant
{
    public function __construct(
        public Authenticatable $user,
        public string $panelId,
        public string $permissionKey,
        public ?int $ttlSeconds,
    ) {}

    public function expiresAt(): ?CarbonImmutable
    {
        if ($this->ttlSeconds === null || $this->ttlSeconds <= 0) {
            return null;
        }

        return CarbonImmutable::now()->addSeconds($this->ttlSeconds);
    }
}
