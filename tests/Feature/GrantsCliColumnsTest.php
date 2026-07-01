<?php

declare(strict_types=1);

use AzGuard\Commands\RevokeCommand;
use AzGuard\Models\DirectGrant;
use AzGuard\Tests\Stubs\UserWithDirectGrants;

/**
 * F1: guard:grants and guard:revoke must target grantable_type/grantable_id
 * (the columns created by the direct_grants migration), never model_type/model_id.
 *
 * Note: RevokeCommand (guard:revoke) is not wired into the service provider's
 * command list, so we register it on the console kernel before invoking it.
 */
beforeEach(function () {
    $this->app[Illuminate\Contracts\Console\Kernel::class]
        ->registerCommand($this->app->make(RevokeCommand::class));
});

it('guard:grants lists a direct grant using grantable columns', function () {
    $user = UserWithDirectGrants::factory()->create();
    $user->directGrants()->create([
        'panel_id' => 'app',
        'permission_key' => 'app.documents.export',
        'expires_at' => null,
    ]);

    $this->artisan('guard:grants', [
        '--model' => UserWithDirectGrants::class,
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('app.documents.export');
});

it('guard:grants filters by --user via grantable_id', function () {
    $target = UserWithDirectGrants::factory()->create();
    $other = UserWithDirectGrants::factory()->create();

    $target->directGrants()->create([
        'panel_id' => 'app',
        'permission_key' => 'app.target.only',
        'expires_at' => null,
    ]);
    $other->directGrants()->create([
        'panel_id' => 'app',
        'permission_key' => 'app.other.only',
        'expires_at' => null,
    ]);

    $this->artisan('guard:grants', [
        '--model' => UserWithDirectGrants::class,
        '--user' => (string) $target->getKey(),
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('app.target.only')
        ->doesntExpectOutputToContain('app.other.only');
});

it('guard:grants warns when no grants exist', function () {
    $this->artisan('guard:grants', [
        '--model' => UserWithDirectGrants::class,
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('No grants found.');
});

it('round-trips: grant -> guard:grants shows it -> guard:revoke removes -> guard:grants no longer shows it', function () {
    $user = UserWithDirectGrants::factory()->create();
    $user->directGrants()->create([
        'panel_id' => 'app',
        'permission_key' => 'app.documents.export',
        'expires_at' => null,
    ]);

    // Grant is stored on grantable columns.
    expect(DirectGrant::query()
        ->where('grantable_type', UserWithDirectGrants::class)
        ->where('grantable_id', $user->getKey())
        ->where('permission_key', 'app.documents.export')
        ->exists())->toBeTrue();

    // guard:grants lists it.
    $this->artisan('guard:grants', [
        '--model' => UserWithDirectGrants::class,
        '--user' => (string) $user->getKey(),
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('app.documents.export');

    // guard:revoke removes it.
    $this->artisan('guard:revoke', [
        'user_id' => (string) $user->getKey(),
        'permission_key' => 'app.documents.export',
        '--panel' => 'app',
        '--model' => UserWithDirectGrants::class,
        '--force' => true,
    ])->assertSuccessful();

    // Row is gone from the grantable columns.
    expect(DirectGrant::query()
        ->where('grantable_type', UserWithDirectGrants::class)
        ->where('grantable_id', $user->getKey())
        ->where('permission_key', 'app.documents.export')
        ->exists())->toBeFalse();

    // guard:grants no longer lists it.
    $this->artisan('guard:grants', [
        '--model' => UserWithDirectGrants::class,
        '--user' => (string) $user->getKey(),
    ])
        ->assertSuccessful()
        ->doesntExpectOutputToContain('app.documents.export');
});

it('guard:revoke --all removes every grant for the user across panels', function () {
    $user = UserWithDirectGrants::factory()->create();
    $user->directGrants()->create(['panel_id' => 'app', 'permission_key' => 'app.a', 'expires_at' => null]);
    $user->directGrants()->create(['panel_id' => 'admin', 'permission_key' => 'admin.b', 'expires_at' => null]);

    $this->artisan('guard:revoke', [
        'user_id' => (string) $user->getKey(),
        '--all' => true,
        '--model' => UserWithDirectGrants::class,
        '--force' => true,
    ])->assertSuccessful();

    expect(DirectGrant::query()
        ->where('grantable_type', UserWithDirectGrants::class)
        ->where('grantable_id', $user->getKey())
        ->count())->toBe(0);
});

it('guard:revoke leaves other users grants intact (scoped by grantable_id)', function () {
    $user = UserWithDirectGrants::factory()->create();
    $other = UserWithDirectGrants::factory()->create();

    $user->directGrants()->create(['panel_id' => 'app', 'permission_key' => 'app.mine', 'expires_at' => null]);
    $other->directGrants()->create(['panel_id' => 'app', 'permission_key' => 'app.theirs', 'expires_at' => null]);

    $this->artisan('guard:revoke', [
        'user_id' => (string) $user->getKey(),
        '--all' => true,
        '--model' => UserWithDirectGrants::class,
        '--force' => true,
    ])->assertSuccessful();

    expect(DirectGrant::query()
        ->where('grantable_id', $other->getKey())
        ->where('permission_key', 'app.theirs')
        ->exists())->toBeTrue();
});
