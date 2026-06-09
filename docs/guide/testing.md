# Testing

AzGuard is designed to be test-friendly. Roles are PHP classes — they're always available without seeding. Permission checks are pure functions — they're easy to assert.

## Setup

For feature tests that touch the database, use `RefreshDatabase` and register the service provider:

```php
use AzGuard\AzGuardServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class MyFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [AzGuardServiceProvider::class];
    }
}
```

## Creating users with roles

```php
public function test_editor_can_view_documents(): void
{
    $user = User::factory()->create();
    $user->assignRole('editor');

    $this->actingAs($user)
        ->get(route('documents.index'))
        ->assertOk();
}

public function test_viewer_cannot_delete(): void
{
    $user     = User::factory()->create();
    $document = Document::factory()->create();
    $user->assignRole('viewer');

    $this->actingAs($user)
        ->delete(route('documents.destroy', $document))
        ->assertForbidden();
}
```

## Testing permission checks directly

```php
public function test_editor_permissions(): void
{
    $user = User::factory()->create();
    $user->assignRole('editor');

    $this->assertTrue($user->hasPermission(DocumentsPermission::View));
    $this->assertTrue($user->hasPermission(DocumentsPermission::Edit));
    $this->assertFalse($user->hasPermission(DocumentsPermission::Delete));

    // hasAnyPermission / hasAllPermissions
    $this->assertTrue($user->hasAnyPermission([
        DocumentsPermission::Edit,
        DocumentsPermission::Delete,
    ]));

    $this->assertFalse($user->hasAllPermissions([
        DocumentsPermission::Edit,
        DocumentsPermission::Delete,  // editor doesn't have Delete
    ]));
}
```

## Testing direct grants

```php
use AzGuard\Grants\GrantBuilder;

public function test_user_with_direct_grant_can_access(): void
{
    $user = User::factory()->create();

    (new GrantBuilder($user))
        ->on('app')
        ->give(DocumentsPermission::View);

    $this->assertTrue($user->hasPermission(DocumentsPermission::View));
}

public function test_expired_grant_is_denied(): void
{
    $user = User::factory()->create();

    (new GrantBuilder($user))
        ->on('app')
        ->give(DocumentsPermission::View)
        ->until(now()->subMinute());   // already expired

    $user->flushPermissions();         // clear in-memory cache

    $this->assertFalse($user->hasPermission(DocumentsPermission::View));
}

public function test_grant_with_ttl_is_active(): void
{
    $user = User::factory()->create();

    (new GrantBuilder($user))
        ->on('app')
        ->ttl(3600)                    // 1 hour from now
        ->give(DocumentsPermission::Export);

    $this->assertTrue($user->hasPermission(DocumentsPermission::Export));
}
```

## Using Gate in tests

```php
public function test_gate_allows_editor(): void
{
    $user = User::factory()->create();
    $user->assignRole('editor');

    $this->actingAs($user);

    // ✅ Always use enum constants in Gate assertions
    $this->assertTrue(Gate::allows(DocumentsPermission::View));
    $this->assertTrue(Gate::allows(DocumentsPermission::Edit));
    $this->assertFalse(Gate::allows(DocumentsPermission::Delete));
}

public function test_gate_with_model(): void
{
    $user     = User::factory()->create();
    $document = Document::factory()->create(['owner_id' => $user->id]);
    $user->assignRole('editor');

    $this->actingAs($user);

    // Routes through DocumentPolicy::update() if registered
    $this->assertTrue(Gate::allows(DocumentsPermission::Edit, $document));
}
```

## Mocking / faking the resolver

For unit tests where you don't want a real database:

```php
use AzGuard\Testing\AzGuardFake;

protected function setUp(): void
{
    parent::setUp();

    // Replace the real resolver with an in-memory fake
    AzGuardFake::install();
}

public function test_service_checks_permission(): void
{
    $user = User::factory()->make(['id' => 1]);

    // Grant in-memory — no DB writes; use enum
    AzGuardFake::grantPermission($user, DocumentsPermission::View);
    AzGuardFake::grantRole($user, 'editor');

    $result = app(DocumentService::class)->canView($user, $document);

    $this->assertTrue($result);
}

public function test_service_denies_without_permission(): void
{
    $user = User::factory()->make(['id' => 2]);
    // No permissions granted

    $this->assertFalse(
        app(DocumentService::class)->canView($user, $document)
    );
}
```

## Pest syntax

```php
it('allows editors to view documents', function () {
    $user = User::factory()->create();
    $user->assignRole('editor');

    expect($user->hasPermission(DocumentsPermission::View))->toBeTrue();
    expect($user->hasPermission(DocumentsPermission::Delete))->toBeFalse();
});

it('forbids unauthenticated access', function () {
    $this->get(route('documents.index'))
        ->assertRedirect(route('login'));
});

it('returns 403 for users without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('documents.index'))
        ->assertForbidden();
});
```

## User factories with roles

Define factory states so tests stay readable:

```php
// database/factories/UserFactory.php
public function editor(): static
{
    return $this->afterCreating(fn (User $user) =>
        $user->assignRole('editor', panel: 'app')
    );
}

public function admin(): static
{
    return $this->afterCreating(fn (User $user) =>
        $user->assignRole('super-admin', panel: 'admin')
    );
}
```

```php
// Clean test setup
$editor = User::factory()->editor()->create();
$admin  = User::factory()->admin()->create();
```

## Asserting forbidden responses

```php
// Unauthenticated — redirected to login
$this->get(route('documents.index'))
    ->assertRedirect(route('login'));

// Authenticated but no permission — 403
$this->actingAs(User::factory()->create())
    ->get(route('documents.index'))
    ->assertForbidden();

// Correct role — passes
$this->actingAs(User::factory()->editor()->create())
    ->get(route('documents.index'))
    ->assertOk();
```

## Tips

- **Flush the permission cache between state changes** in a single test: `$user->flushPermissions()`.
- **Use `assertForbidden()` not `assertStatus(403)`** for readable test output.
- **Test both sides.** For every permission you assert as `true`, also test what a user *without* it sees.
- **Disable persistent cache in tests** — add `'cache' => ['enabled' => false]` to your `config/az-guard.php` test override.
