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

The migration creates five tables: `az_guard_roles`, `az_guard_model_has_roles`, `az_guard_model_has_scopes`, `az_guard_role_permissions`, and `az_guard_direct_grants`.

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
use AzGuard\Contracts\PanelProviderInterface;

class AppPanelProvider implements PanelProviderInterface
{
    public function panel(): string { return 'app'; }

    public function permissions(): array
    {
        return [
            DocumentsPermission::class,
            UsersPermission::class,
        ];
    }

    public function roles(): array
    {
        return [
            EditorRole::class,
            ViewerRole::class,
        ];
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
php artisan azguard:make-permission App DocumentsPermission
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
php artisan azguard:make-role App EditorRole
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
$user->assignRole('editor');  // by name, panel auto-resolved
// or explicitly:
$user->assignRole(EditorRole::class, panel: 'app');

// Check (multiple ways — all equivalent)
$user->hasPermission(DocumentsPermission::View);        // true
$user->hasPermission('app.documents.view');              // true
Gate::allows('app.documents.view');                     // true (Gate facade)
request()->user()->can('app.documents.view');           // true (Auth helper)
```

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
