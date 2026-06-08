# Roles

In AzGuard, a role is a **PHP class** that declares which permissions it grants. Roles are code — not database records — which means they are version-controlled, diffable, and always consistent.

## Static roles (recommended)

Static roles are PHP classes that implement `RoleInterface`. Their permissions are declared in code.

```php
// app/AzGuard/App/Roles/EditorRole.php
namespace App\AzGuard\App\Roles;

use AzGuard\Contracts\RoleInterface;
use App\AzGuard\App\Permissions\DocumentsPermission;
use App\AzGuard\App\Permissions\CommentsPermission;

class EditorRole implements RoleInterface
{
    public function getName(): string  { return 'editor'; }
    public function getPanel(): string { return 'app'; }
    public function getLevel(): int    { return 10; }

    public function permissions(): array
    {
        return [
            DocumentsPermission::View,
            DocumentsPermission::Create,
            DocumentsPermission::Edit,
            CommentsPermission::View,
            CommentsPermission::Create,
        ];
    }
}
```

### Role levels

`getLevel()` is an integer used for role hierarchy comparisons. Higher number = higher privilege:

| Role | Level |
|---|---|
| `viewer` | 1 |
| `editor` | 10 |
| `manager` | 50 |
| `admin` | 100 |

Levels are **not** used for permission inheritance (a higher-level role does not automatically inherit lower-level permissions). They exist for your application logic, e.g., `$user->hasRoleLevel('>= 50')`.

## Generating roles

```bash
php artisan azguard:make-role App EditorRole
php artisan azguard:make-role Admin SuperAdminRole
```

## Assigning roles

```php
// By class name
$user->assignRole(EditorRole::class);

// By string name (panel auto-resolved from registration)
$user->assignRole('editor');

// Explicitly specifying the panel
$user->assignRole('editor', panel: 'app');

// Multiple roles at once
$user->syncRoles(['editor', 'viewer']);

// Remove a single role
$user->removeRole('editor');

// Remove all roles
$user->syncRoles([]);
```

## Checking roles

```php
$user->hasRole('editor');               // bool
$user->hasRole(EditorRole::class);      // bool
$user->hasAnyRole(['editor', 'admin']); // bool — any of the listed roles
$user->hasAllRoles(['editor', 'admin']); // bool — must have all
$user->getRoleNames();                  // Collection<string>
```

## Dynamic (DB-backed) roles

For admin UIs where roles are managed at runtime, you can use the `DynamicRole` model:

```php
use AzGuard\Models\DynamicRole;

// Create a role at runtime
$role = DynamicRole::create([
    'name'    => 'tenant-admin',
    'panel'   => 'app',
    'level'   => 20,
]);

// Attach permissions
$role->givePermissions([
    DocumentsPermission::View,
    DocumentsPermission::Create,
]);

// Assign to user
$user->assignRole($role);
```

Dynamic roles are stored in `az_guard_roles` and their permissions in `az_guard_role_permissions`.

## Listing roles

```bash
php artisan azguard:list-roles
php artisan azguard:list-roles --panel=app
```

## Best practices

- **Use static roles as the source of truth.** Dynamic roles are useful for multi-tenant customization, but your base roles should always be in code.
- **One role class per role.** `EditorRole`, `ViewerRole`, `AdminRole` — each in its own file.
- **Keep permission lists explicit.** Avoid loops or computed permission arrays — the list should be readable at a glance.
- **Use [Direct Grants](/guide/direct-grants) for exceptions.** If one user needs an extra permission, don't create a new role — grant it directly.
