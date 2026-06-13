# Direct Grants

::: warning Prefer roles over direct grants
Direct Grants are an **exception** mechanism, not a primary access-control pattern. The correct default is to assign permissions to roles, and assign roles to users. Use direct grants only when a specific user needs a temporary or one-off permission that doesn't warrant a new role.

See [Roles vs Permissions](./best-practices.md#roles-vs-permissions) for the full guidance.
:::

Direct Grants let you assign a permission **directly to a user** without creating a role for it. Typical use cases:

- Temporary access (beta feature, limited-time export)
- One-off override for a specific user
- Feature flags scoped to individual accounts

## When to use direct grants

| Situation | Recommendation |
|---|---|
| One user needs a permission no one else has | ✅ Direct grant |
| A user needs temporary access (expires in N hours) | ✅ Direct grant with TTL |
| Multiple users need the same permission | ❌ Create a role instead |
| Permission is part of a user's everyday job | ❌ Assign a role instead |
| You find yourself granting the same permission to 5+ users | ❌ Time to create a role |

## Connecting the trait

Add `HasDirectGrants` to your User model alongside `HasAzGuard`:

```php
use AzGuard\Concerns\HasAzGuard;
use AzGuard\Concerns\HasDirectGrants;

class User extends Authenticatable
{
    use HasAzGuard, HasDirectGrants;
}
```

::: tip
`HasDirectGrants` extends `hasPermission()`: it now checks roles **and** direct grants. No other code changes needed.
:::

## Granting a permission

### Fluent API

```php
use AzGuard\Facades\AzGuard;

// Permanent
AzGuard::forUser($user)
    ->on('app')
    ->grant(DocumentsPermission::Export);

// With a 1-hour TTL
AzGuard::forUser($user)
    ->on('app')
    ->ttl(3600)
    ->grant(DocumentsPermission::Export);

// Shorthand
AzGuard::grant($user, DocumentsPermission::Export, 'app', ttl: 3600);
```

::: info Idempotent
Calling `give()` on an already-granted permission updates `expires_at` without creating a duplicate. Safe to call multiple times.
:::

### Artisan

```bash
# Permanent
php artisan az-guard:grant {user-id} {permission} {panel}

# With TTL (seconds)
php artisan az-guard:grant 42 app.documents.export app --ttl=3600

# Different model
php artisan az-guard:grant 7 admin.reports.view admin --model=App\\Models\\Admin
```

## Revoking a grant

```php
// Single permission — enum
AzGuard::forUser($user)->on('app')->revoke(DocumentsPermission::Export);
AzGuard::revoke($user, DocumentsPermission::Export, 'app');

// All grants for a panel
AzGuard::forUser($user)->on('app')->revokeAll();
```

```bash
# Artisan
php artisan az-guard:revoke-grant 42 app.documents.export app
php artisan az-guard:revoke-grant 42 - app --all --force
```

## Checking a grant

```php
// On the User model — enum or string accepted, enum preferred
$user->hasDirectGrant(DocumentsPermission::Export);
$user->hasDirectGrant(DocumentsPermission::Export, 'app');

// Via Laravel Gate
Gate::allows('direct-grant', DocumentsPermission::Export);
Gate::allows('direct-grant', [DocumentsPermission::Export, 'app']);

// List active grants
$grants = AzGuard::forUser($user)->on('app')->list();
$grants = AzGuard::activeGrants($user, 'app');
```

## Blade

```blade
@azdirect(\App\AzGuard\App\Permissions\DocumentsPermission::Export->value)
    <button>Export</button>
@endazdirect

{{-- Explicit panel --}}
@azdirect(\App\AzGuard\App\Permissions\DocumentsPermission::Export->value, 'app')
    <button>Export</button>
@endazdirect
```

## Route middleware

```php
// az.grant:{permission},{panel} — string form required by middleware
Route::get('/export', ExportController::class)
    ->middleware('az.grant:' . DocumentsPermission::Export->value . ',app');

// Panel inferred from AzGuard::currentPanel() if omitted
Route::get('/export', ExportController::class)
    ->middleware('az.grant:' . DocumentsPermission::Export->value);
```

| Situation | HTTP status |
|---|---|
| Not authenticated | 401 |
| Grant missing or expired | 403 |
| Grant active | passes through |

## TTL and expiry

A grant with `expires_at < now()` is treated as inactive in all checks. Expired records are cleaned up by the scheduler:

```php
// bootstrap/app.php
->withSchedule(function (Schedule $schedule) {
    $schedule->command('az-guard:prune-grants')->daily();
})
```

```bash
php artisan az-guard:prune-grants
php artisan az-guard:prune-grants --panel=app
```

## Events

| Event | When dispatched |
|---|---|
| `GrantGiven` | After every `give()` call |
| `GrantRevoked` | After every `revoke()` / `revokeAll()` call |

```php
use AzGuard\Events\GrantGiven;
use AzGuard\Events\GrantRevoked;

Event::listen(GrantGiven::class, function (GrantGiven $event): void {
    Log::info("Grant [{$event->permissionKey}] issued to user #{$event->user->getAuthIdentifier()}");
});

Event::listen(GrantRevoked::class, function (GrantRevoked $event): void {
    // e.g., invalidate API cache
});
```

## Quick reference

| Method | Code |
|---|---|
| Fluent grant | `AzGuard::forUser($u)->on('app')->ttl(3600)->grant(DocumentsPermission::Export)` |
| Shorthand grant | `AzGuard::grant($u, DocumentsPermission::Export, 'app', ttl: 3600)` |
| Artisan grant | `php artisan az-guard:grant {id} {perm} {panel}` |
| Revoke | `AzGuard::forUser($u)->on('app')->revoke(DocumentsPermission::Export)` |
| Check (model) | `$user->hasDirectGrant(DocumentsPermission::Export, 'app')` |
| Check (Gate) | `Gate::allows('direct-grant', DocumentsPermission::Export)` |
| Blade | `@azdirect(DocumentsPermission::Export->value) ... @endazdirect` |
| Middleware | `->middleware('az.grant:' . DocumentsPermission::Export->value . ',app')` |
