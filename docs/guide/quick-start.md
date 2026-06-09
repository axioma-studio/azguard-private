# Quick Start

Get from zero to a working permission check in under 5 minutes.

## Requirements

- PHP 8.2+
- Laravel 11+
- A database supported by Laravel (MySQL, PostgreSQL, SQLite)

## 1. Install

```bash
composer require azguard/azguard
php artisan vendor:publish --tag=az-guard-config
php artisan migrate
```

The migration creates four tables: `roles`, `model_has_roles`, `model_has_scopes`, and `az_direct_grants`.

## 2. Add the trait to your User model

```php
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable
{
    use HasAzGuard;
}
```

The trait adds `hasPermission()`, `checkPermission()`, `assignRole()`, `removeRole()`, `syncRoles()`, and `flushPermissions()`.

## 3. Register a panel

A **panel** is an isolated permission namespace — `app`, `admin`, `api`, etc.  
Create a panel provider and list it in `config/az-guard.php`:

```php
// app/AzGuard/Panels/AppPanelProvider.php
namespace App\AzGuard\Panels;

use AzGuard\PanelProvider;
use AzGuard\Support\Panel;
use App\AzGuard\App\Permissions\DocumentsPermission;
use App\AzGuard\App\Permissions\UsersPermission;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->permissionEnums([
                DocumentsPermission::class,
                UsersPermission::class,
            ]);
    }
}
```

```php
// config/az-guard.php
'panels' => [
    \App\AzGuard\Panels\AppPanelProvider::class,
],
```

## 4. Create a permission enum

```bash
php artisan make:guard-permission App DocumentsPermission
```

```php
// app/AzGuard/App/Permissions/DocumentsPermission.php
namespace App\AzGuard\App\Permissions;

use AzGuard\Contracts\PermissionInterface;
use AzGuard\Attributes\GateAbility;

enum DocumentsPermission: string implements PermissionInterface
{
    #[GateAbility]  // registers Gate ability 'app.documents.view'
    case View   = 'documents.view';
    #[GateAbility]
    case Create = 'documents.create';
    #[GateAbility]
    case Edit   = 'documents.edit';
    #[GateAbility]
    case Delete = 'documents.delete';
}
```

The full Gate key is `{panel}.{permission_value}` → `app.documents.view`.

## 5. Create a role

```bash
php artisan make:guard-role App EditorRole
```

```php
// app/AzGuard/App/Roles/EditorRole.php
namespace App\AzGuard\App\Roles;

use AzGuard\Contracts\RoleInterface;
use App\AzGuard\App\Permissions\DocumentsPermission;

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
        ];
    }
}
```

## 6. Assign and check

```php
// Assign
$user->assignRole('editor');                 // by name, panel auto-resolved
$user->assignRole(EditorRole::class);        // by class (preferred)
$user->assignRole(EditorRole::class, panel: 'app');  // explicit panel

// ✅ Check — always use enum constants
$user->hasPermission(DocumentsPermission::View);   // true
Gate::allows(DocumentsPermission::View);           // true (Gate facade)
request()->user()->can(DocumentsPermission::View); // true (Auth helper)

// ⚠️  String form — accepted for backward compatibility, but not recommended
// $user->hasPermission('app.documents.view');     // works, but avoid
```

::: tip Use enum constants everywhere
Raw strings like `'app.documents.view'` are accepted but not recommended — a typo is a silent security hole that no IDE or static analyser will catch. Always use the enum case: `DocumentsPermission::View`.
:::

## Verify the setup

```bash
php artisan azguard:doctor
```

The doctor checks:
- All panel providers are registered and resolvable
- Every role class implements `RoleInterface`
- No orphan permission keys in the database
- Migrations are up to date

## Next steps

- [Panels](/guide/panels) — understand `app` vs `admin` isolation
- [Permissions](/guide/permissions) — naming conventions, `#[RoleOnly]`, TypeScript export
- [Roles](/guide/roles) — static and dynamic (DB-backed) roles
- [HTTP Access](/guide/http-access) — `#[CheckPermission]` on controllers and middleware
- [Direct Grants](/guide/direct-grants) — per-user permissions without a role
- [Super-Admin](/guide/super-admin) — wildcard access bypass
