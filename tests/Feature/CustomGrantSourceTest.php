<?php

declare(strict_types=1);

use AzGuard\Facades\AzGuard;
use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Values\PermissionSet;
use AzGuard\Tests\Stubs\User;
use Illuminate\Contracts\Auth\Authenticatable;

// A custom source that grants a real catalog key on the 'test' panel.
class StubCustomGrantSource implements GrantSource
{
    public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet
    {
        return $panelId === 'test'
            ? PermissionSet::fromKeys(['test.post.view'])
            : PermissionSet::empty();
    }

    public function priority(): int
    {
        return 50;
    }
}

/**
 * H4: AzGuard::registerGrantSource() plugs a custom source into the resolution
 * chain without touching the service provider.
 */
it('resolves permissions from a registered custom grant source', function () {
    AzGuard::registerGrantSource(StubCustomGrantSource::class);

    // Rebuild the scoped resolver so it re-reads the freshly tagged source.
    app()->forgetScopedInstances();

    $user = User::factory()->create(); // no roles at all

    expect($user->hasPermission('test.post.view', 'test'))->toBeTrue()
        ->and($user->hasPermission('test.post.delete', 'test'))->toBeFalse();
});
