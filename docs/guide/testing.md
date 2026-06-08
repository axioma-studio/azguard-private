# Testing

AzGuard is designed to be test-friendly. This page covers patterns for unit tests, feature tests, and using fakes.

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
    $user = User::factory()->create();
    $user->assignRole('viewer');

    $this->actingAs($user)
        ->delete(route('documents.destroy', $document))
        ->assertForbidden();
}
```

## Testing permission checks directly

```php
public function test_editor_has_edit_permission(): void
{
    $user = User::factory()->create();
    $user->assignRole('editor');

    $this->assertTrue($user->hasPermission(DocumentsPermission::Edit));
    $this->assertFalse($user->hasPermission(DocumentsPermission::Delete));
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
        ->until(now()->subMinute());  // already expired

    $this->assertFalse($user->hasPermission(DocumentsPermission::View));
}
```

## Using Gate in tests

```php
public function test_gate_allows_editor(): void
{
    $user = User::factory()->create();
    $user->assignRole('editor');

    $this->actingAs($user);

    $this->assertTrue(Gate::allows('app.documents.view'));
    $this->assertFalse(Gate::allows('app.documents.delete'));
}
```

## Mocking / faking the resolver

For unit tests where you don't want a database:

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

    // Give the fake user a permission
    AzGuardFake::grantPermission($user, 'app.documents.view');

    $result = app(DocumentService::class)->canView($user, $document);

    $this->assertTrue($result);
}
```

## Asserting forbidden responses

```php
public function test_unauthenticated_user_is_redirected(): void
{
    $this->get(route('documents.index'))
        ->assertRedirect(route('login'));
}

public function test_user_without_permission_gets_403(): void
{
    $user = User::factory()->create();  // no roles assigned

    $this->actingAs($user)
        ->get(route('documents.index'))
        ->assertForbidden();
}
```

## Tips

- **Flush the permission cache between tests** if you're modifying roles/grants within a single test class: `$user->flushPermissions()`.
- **Use `assertForbidden()` not `assertStatus(403)`** for readable test output.
- **Test the negative case.** For every permission you test as `true`, also test what a user *without* it sees.
