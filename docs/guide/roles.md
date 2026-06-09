# Roles

In AzGuard, a role is a **PHP class** that declares which permissions it grants. Roles are code — not database records — which means they are version-controlled, reviewable in PRs, and always consistent.

## Static roles (recommended)

Static roles implement `RoleInterface`. Their permissions are declared in code and never drift from what's deployed.

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

`getLevel()` is an integer for hierarchy comparisons. Higher = higher privilege:

| Role | Level |
|---|---|
| `viewer` | 1 |
| `editor` | 10 |
| `manager` | 50 |
| `admin` | 100 |
| `super-admin` | 999 |

Levels are **not** used for permission inheritance — a `manager` does not automatically inherit `editor` permissions unless you explicitly list them. They exist for your app logic:

```php
// True if the user's highest role level is >= 50
$user->hasRoleLevel('>= 50');

// Returns the numeric level of the user's highest role
$user->getRoleLevel();   // e.g. 50

// Examples: '>= 50', '> 10', '== 100', '< 100'
```

## Generating roles

```bash
php artisan azguard:make-role App EditorRole
php artisan azguard:make-role Admin SuperAdminRole
```

## Assigning roles

```php
// By class name (most explicit — preferred)
$user->assignRole(EditorRole::class);

// By string name (panel auto-resolved from registration)
$user->assignRole('editor');

// Explicit panel — required when the same name exists in multiple panels
$user->assignRole('admin', panel: 'admin');

// Multiple roles at once — replaces the full list
$user->syncRoles([EditorRole::class, ViewerRole::class]);

// Remove a single role
$user->removeRole(EditorRole::class);
$user->removeRole('editor');

// Remove all roles
$user->syncRoles([]);
```

::: warning syncRoles([]) removes everything
`syncRoles()` always replaces the complete role list. Pass only the roles you want the user to have after the call. An empty array removes all roles.
:::

## Checking roles

```php
$user->hasRole('editor');                     // bool — by string name
$user->hasRole(EditorRole::class);            // bool — by class
$user->hasAnyRole(['editor', 'admin']);        // true if at least one matches
$user->hasAllRoles(['editor', 'moderator']);   // true only if ALL match
$user->getRoleNames();                         // Collection<string>
$user->getRoles();                             // Collection of role objects
$user->hasRoleLevel('>= 50');                 // compare against level
$user->getRoleLevel();                         // int: highest level
```

## Inspecting assigned roles

```php
// All role names as strings
$user->getRoleNames();            // Collection<string> — ['editor', 'viewer']

// Permissions inherited through roles
$user->getPermissionsViaRoles();  // Collection<string>

// Combined: permissions from roles + direct grants
$user->getAllPermissions();        // Collection<string>
```

## Query users by role

AzGuard provides Eloquent scopes:

```php
// All users with 'editor' role
User::role('editor')->get();
User::role(EditorRole::class)->get();

// Users with any of these roles
User::role(['editor', 'admin'])->get();

// Users WITHOUT a specific role
User::withoutRole('editor')->get();
User::withoutRole(['editor', 'viewer'])->get();

// Combine with other clauses
User::role('editor')
    ->where('active', true)
    ->orderBy('name')
    ->paginate();

// Eager-load roles to avoid N+1 in list views
User::with('azRoles')->paginate();

// Users with no roles at all
User::doesntHave('azRoles')->get();
```

## Dynamic (DB-backed) roles

For admin UIs where roles are managed at runtime:

```php
use AzGuard\Models\DynamicRole;

// Create
$role = DynamicRole::create([
    'name'  => 'tenant-admin',
    'panel' => 'app',
    'level' => 20,
]);

// Attach permissions
$role->givePermissions([
    DocumentsPermission::View,
    DocumentsPermission::Create,
]);

// Sync (replaces the permission list)
$role->syncPermissions([DocumentsPermission::View]);

// Revoke one
$role->revokePermission(DocumentsPermission::Create);

// Assign to a user
$user->assignRole($role);

// List all dynamic roles
DynamicRole::where('panel', 'app')->get();
```

Dynamic roles are stored in `az_guard_roles`; their permissions in `az_guard_role_permissions`.

## Syncing static roles to DB

If your app uses the `az_guard_roles` table as a reference (e.g., for Filament dropdowns), keep it in sync after deploying new PHP role classes:

```bash
php artisan azguard:sync-roles
php artisan azguard:sync-roles --panel=app
```

This is safe to run in CI/CD pipelines.

## Listing roles

```bash
php artisan azguard:list-roles
php artisan azguard:list-roles --panel=app
```

## Gotchas

**Role names are panel-scoped.** Two roles named `admin` in panels `app` and `admin` are different roles. Specify the panel explicitly when ambiguous: `$user->assignRole('admin', panel: 'admin')`.

**`syncRoles([])` removes all roles.** This is intentional. Pass only the roles you want the user to have after the call.

**Role names must be unique within a panel.** Registering two roles with the same `getName()` in the same panel will cause a conflict at boot time.

**Levels are not inherited.** A level-100 `SuperAdmin` does not automatically include all lower-level permissions. List them explicitly.

## Best practices

- **Use static roles as the source of truth.** Dynamic roles are useful for multi-tenant customization, but your base roles should always be in code.
- **One class per role.** `EditorRole`, `ViewerRole`, `AdminRole` — each in its own file.
- **Keep permission lists readable.** Avoid computed permission arrays or loops — the list should be scannable at a glance.
- **Use [Direct Grants](./direct-grants.md) for exceptions.** If one user needs an extra permission, grant it directly rather than creating a narrow role used by a single user.
