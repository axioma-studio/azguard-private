# Recipe: Super-Admin Wildcard

A super-admin bypasses all Gate checks. AzGuard implements this via a role whose `permissions()` returns `['*']`.

## Define the role

```php
namespace App\Guards\Admin\Roles;

use AzGuard\Contracts\RoleInterface;

class SuperAdminRole implements RoleInterface
{
    public function getName(): string  { return 'super-admin'; }
    public function getPanel(): string { return 'admin'; }
    public function getLevel(): int    { return 100; }

    public function permissions(): array
    {
        return ['*'];  // wildcard — Gate::before returns true for all abilities
    }
}
```

Register in `AdminPanelProvider::roles()`.

## How it works

AzGuard's `Gate::before` callback checks whether any of the user's resolved permissions for the current panel is `'*'`. If so, it returns `true` before the policy method is called.

```php
// Internally — simplified
Gate::before(function (User $user, string $ability) {
    if (in_array('*', $user->resolvedPermissions(panel: currentPanel()))) {
        return true;
    }
});
```

## Disabling wildcard

If you want to disable wildcard support application-wide:

```php
// config/az-guard.php
'wildcard' => false,
```

With `wildcard: false`, a `*` permission is treated as a literal string and will never match any ability.

::: danger
Assign the super-admin role only to infrastructure accounts. For human admins, prefer explicit role permissions so your audit log is meaningful.
:::
