# Super-Admin

A super-admin is a user who bypasses all permission checks. AzGuard implements this via a **wildcard grant** — a special `PermissionSet` that returns `true` for any permission key.

## Option 1: Gate before-hook (recommended)

This is the simplest and most Laravel-idiomatic approach:

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::before(function ($user, $ability) {
        if ($user->isSuperAdmin()) {
            return true;  // short-circuits all subsequent checks
        }
    });
}
```

Define `isSuperAdmin()` in your User model:

```php
public function isSuperAdmin(): bool
{
    return $this->hasRole(SuperAdminRole::class);  // by class (preferred); 'super-admin' by name also works
}
```

## Option 2: Wildcard role

Create a role that grants the wildcard permission set:

```php
use AzGuard\Roles\BaseRole;

class SuperAdminRole extends BaseRole
{
    public function getName(): string { return 'super-admin'; }
    public function getLevel(): int   { return 999; }

    public function permissions(): array
    {
        return ['*'];  // wildcard — grants everything in this panel
    }
}
```

Register it on the panel via `roleClasses([SuperAdminRole::class])` in your panel provider.

Then assign the role to the user as normal. Run `php artisan guard:sync-roles` first so the role class is mirrored into the DB before assigning:

```php
$user->assignRole(SuperAdminRole::class);     // by class (preferred); 'super-admin' by name also works

$user->hasPermission(DocumentsPermission::View);  // true — enum case, scoped to the panel
$user->hasPermission('app.anything.at.all');      // true — full panel-prefixed key
```

## Option 3: Direct wildcard grant

```php
use AzGuard\Facades\AzGuard;

// Grant superadmin access for 24 hours (TTL in seconds)
AzGuard::forUser($user)
    ->on('app')
    ->ttl(86400)
    ->grant('*');
```

## Checking in tests

```php
// Quickly make any user a super-admin in a test
$user->assignRole(SuperAdminRole::class);     // 'super-admin' by name also works
$this->actingAs($user);

$this->get('/admin/users')->assertOk();
```

## Scope of wildcard

Wildcard access applies **per panel** — a role's wildcard only covers the panel that registered the role class. A super-admin role registered on `app` does not automatically have access to `admin`:

```php
$user->assignRole(SuperAdminRole::class);  // role registered on the 'app' panel

$user->hasPermission('app.documents.delete');  // true
$user->hasPermission('admin.users.delete');    // false — different panel
```

To grant access across all panels, register a wildcard role on each panel, or use the Gate before-hook approach which runs before panel resolution.
