# Recipe: Super-Admin Wildcard

A super-admin bypasses all Gate checks. AzGuard implements this via a role whose `permissions()` returns `['*']`.

## Define the role

```php
namespace App\Guards\Admin\Roles;

use AzGuard\Roles\BaseRole;

class SuperAdminRole extends BaseRole
{
    public function getName(): string { return 'super-admin'; }
    public function getLevel(): int   { return 100; }

    public function permissions(): array
    {
        return ['*'];  // wildcard — Gate::before returns true for all abilities
    }
}
```

Register it on the admin panel via `roleClasses([SuperAdminRole::class])` in your panel provider.

## How it works

AzGuard's `Gate::before` callback resolves the user's `PermissionSet` for the panel the ability belongs to. A wildcard set (`['*']`) matches every key, so the check returns `true` before the policy method is called.

```php
// Equivalent to what the Gate::before hook does internally
if ($user->permissionSet('admin')->isWildcard()) {
    // grants every ability on the 'admin' panel
}
```

## Segment wildcards

The full `'*'` superadmin wildcard above always works. Segment wildcards like
`'admin.*'` are an opt-in feature, disabled by default. Enable them in config:

```php
// config/az-guard.php
'features' => [
    'wildcard_permission' => true,  // allow segment wildcards like 'admin.*'
],
```

::: danger
Assign the super-admin role only to infrastructure accounts. For human admins, prefer explicit role permissions so your audit log is meaningful.
:::
