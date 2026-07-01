<?php

declare(strict_types=1);

namespace AzGuard\Tests\Stubs;

use AzGuard\Models\DirectGrant;
use Illuminate\Database\Eloquent\Builder;
use Override;

/**
 * Custom direct-grant model stub for proving Config::directGrantModel() is
 * honoured on the read path. Shares the same table as DirectGrant but adds a
 * distinctive global scope: only rows whose permission_key is prefixed with
 * "custom." survive the query. If DirectGrantSource used the hardcoded
 * DirectGrant model instead of this one, that scope would not apply and a
 * plain "app.*" key would leak through — so the scope makes the choice of
 * model observable from a test.
 */
class CustomDirectGrant extends DirectGrant
{
    #[Override]
    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('custom_only', static function (Builder $query): void {
            $query->where('permission_key', 'like', 'custom.%');
        });
    }
}
