# Roles

AzGuard supports two kinds of roles: **static** (PHP class, code-first) and **custom** (DB-backed, created via Filament or API at runtime). Both implement `RoleInterface` and resolve to the same permission check path.

## Static roles (code-first)

A static role is a PHP class committed to your repository.

```bash
php artisan azguard:make-role App EditorRole
```

```php
namespace App\Guards\App\Roles;

use AzGuard\Contracts\RoleInterface;
use App\Guards\App\AppGuard;
use App\Guards\App\Permissions\DocumentsPermission;
use App\Guards\App\Permissions\ProjectsPermission;

class EditorRole implements RoleInterface
{
    public function getName(): string  { return 'editor'; }
    public function getPanel(): string { return 'app'; }
    public function getLevel(): int    { return 10; }  // higher = more privileged

    public function permissions(): array
    {
        return [
            AppGuard::permission(DocumentsPermission::View),
            AppGuard::permission(DocumentsPermission::Create),
            AppGuard::permission(DocumentsPermission::Edit),
            AppGuard::permission(ProjectsPermission::View),
        ];
    }
}
```

### Assigning a static role

```php
$user->assignRole(EditorRole::class, panel: 'app');

// Remove
$user->removeRole(EditorRole::class, panel: 'app');
```

## Custom roles (DB-backed)

Custom roles are created at runtime — by an admin in the Filament UI, or programmatically. They are stored in the `az_guard_roles` table.

```php
use AzGuard\Models\AzRole;

$role = AzRole::create([
    'name'  => 'regional-manager',
    'panel' => 'app',
    'level' => 5,
]);

// Attach permissions (resolved strings)
$role->permissions()->attach([
    AppGuard::permission(DocumentsPermission::View),
    AppGuard::permission(ProjectsPermission::View),
]);

// Assign to user
$user->assignRole($role);
```

Custom roles appear alongside static roles in `$user->azRoles()` and are cached identically.

::: tip Filament
Use `RoleResource` from `azguard/filament` to let admins create and manage custom roles without code. See [Filament integration](/guide/filament).
:::

## Role levels

Role levels control precedence when multiple roles are assigned. A higher level wins conflicts. Levels do **not** imply role inheritance — every role's `permissions()` is independent.

| Level | Example role |
|---|---|
| 100 | SuperAdmin (wildcard) |
| 50 | Admin |
| 10 | Editor |
| 1 | Viewer |

## Checking roles

```php
$user->hasAzRole('editor', panel: 'app');         // by name
$user->hasAzRole(EditorRole::class, panel: 'app'); // by class
```

Prefer permission checks (`hasAzPermission`) over role checks in business logic. Roles are configuration; permissions are the contract.
