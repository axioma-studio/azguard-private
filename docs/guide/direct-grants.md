# Direct Grants

Direct Grants let you assign a **specific permission directly to a user**, bypassing roles entirely.

::: warning Use direct grants as the exception, not the rule
The correct approach for most applications is: create a role, attach permissions to it, assign the role to users. Direct grants exist for **exceptional cases** — temporary access, beta features for specific users, one-off export rights.

If you find yourself using direct grants as the primary authorization pattern, [reconsider your role structure](./best-practices.md).
:::

Typical scenarios:
- Temporary access (grant expires after N hours)
- Beta feature for selected users
- One-time export or operation permission
- Emergency access override without redeploying code

## Enable the trait

Add `HasDirectGrants` to your `User` model alongside `HasAzGuard`:

```php
use AzGuard\Concerns\HasAzGuard;
use AzGuard\Concerns\HasDirectGrants;

class User extends Authenticatable
{
    use HasAzGuard, HasDirectGrants;
}
```

::: tip
`HasDirectGrants` extends `hasPermission()`: it now checks **role permissions** and **direct grants** transparently — the rest of your code does not need to change.
:::

## Grant a permission

### Fluent API

```php
use AzGuard\Facades\AzGuard;

// Permanent grant
AzGuard::forUser($user)
    ->on('app')
    ->give('app.documents.export');

// Grant with TTL (1 hour)
AzGuard::forUser($user)
    ->on('app')
    ->ttl(3600)
    ->give('app.documents.export');

// Shorthand helper
AzGuard::grantDirect($user, 'app.documents.export', 'app', ttl: 3600);
```

::: info Idempotent
Calling `give()` again updates `expires_at` without creating a duplicate. Safe to call multiple times.
:::

### Artisan

```bash
# Permanent
php artisan az-guard:grant {user-id} {permission} {panel}

# With TTL
php artisan az-guard:grant 42 app.documents.export app --ttl=3600

# Custom model
php artisan az-guard:grant 7 admin.reports.view admin --model=App\\Models\\Admin
```

## Revoke a grant

```php
// Single key
AzGuard::forUser($user)->on('app')->revoke('app.documents.export');
AzGuard::revokeDirect($user, 'app.documents.export', 'app');

// All grants for a panel
AzGuard::forUser($user)->on('app')->revokeAll();
```

```bash
php artisan az-guard:revoke-grant 42 app.documents.export app
php artisan az-guard:revoke-grant 42 - app --all --force
```

## Check for a grant

```php
// On the User model
$user->hasDirectGrant('app.documents.export');          // current panel
$user->hasDirectGrant('app.documents.export', 'app');   // explicit panel

// Via Laravel Gate
Gate::allows('direct-grant', 'app.documents.export');
Gate::allows('direct-grant', ['app.documents.export', 'app']);

// List all active grants
$grants = AzGuard::forUser($user)->on('app')->list();
$grants = AzGuard::activeGrants($user, 'app');
```

## Blade

```blade
{{-- Check direct grant (current panel) --}}
@azdirect('app.documents.export')
    <button>Export</button>
@endazdirect

{{-- Explicit panel --}}
@azdirect('app.documents.export', 'app')
    <button>Export</button>
@endazdirect
```

## Route middleware

```php
// az.grant:{permission},{panel}
Route::get('/export', ExportController::class)
    ->middleware('az.grant:app.documents.export,app');

// Panel resolved from AzGuard::currentPanel() when omitted:
Route::get('/export', ExportController::class)
    ->middleware('az.grant:app.documents.export');
```

| State | HTTP |
|---|---|
| Not authenticated | 401 |
| Grant missing or expired | 403 |
| Grant active | 200 |

## TTL and expiry

A grant with `expires_at < now()` is considered invalid in all checks. Expired records are cleaned by the scheduler:

```php
// bootstrap/app.php
->withSchedule(function (Schedule $schedule) {
    $schedule->command('az-guard:prune-grants')->daily();
})
```

Or manually:

```bash
php artisan az-guard:prune-grants
php artisan az-guard:prune-grants --panel=app
```

## Events

| Event | When |
|---|---|
| `GrantGiven` | After each `give()` |
| `GrantRevoked` | After each `revoke()` / `revokeAll()` |

```php
use AzGuard\Events\GrantGiven;
use AzGuard\Events\GrantRevoked;

Event::listen(GrantGiven::class, function (GrantGiven $event): void {
    Log::info("Grant [{$event->permissionKey}] given to user #{$event->user->getAuthIdentifier()}");
});

Event::listen(GrantRevoked::class, function (GrantRevoked $event): void {
    // e.g. invalidate API cache
});
```

## Quick reference

| Method | API |
|---|---|
| Fluent | `AzGuard::forUser($u)->on('app')->ttl(3600)->give('...')` |
| Shorthand | `AzGuard::grantDirect($u, '...', 'app', ttl: 3600)` |
| Artisan | `php artisan az-guard:grant {id} {perm} {panel}` |
| Blade | `@azdirect('app.x.view') ... @endazdirect` |
| Middleware | `->middleware('az.grant:app.x.view,app')` |
| Gate | `Gate::allows('direct-grant', 'app.x.view')` |
| Model | `$user->hasDirectGrant('app.x.view', 'app')` |
