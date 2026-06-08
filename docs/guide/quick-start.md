# Quick Start

Get from zero to a working permission check in 5 steps.

## 1. Install

```bash
composer require azguard/azguard
php artisan vendor:publish --tag=az-guard-config
php artisan migrate
```

## 2. Add the trait to your User model

```php
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable
{
    use HasAzGuard;
}
```

The trait adds `hasAzPermission()`, `assignRole()`, `removeRole()`, `giveAzPermission()`, and `revokeAzPermission()`.

## 3. Register a panel

Create a panel provider and list it in `config/az-guard.php`:

```php
// app/AzGuard/Panels/AppPanelProvider.php
use AzGuard\Contracts\PanelProviderInterface;

class AppPanelProvider implements PanelProviderInterface
{
    public function panel(): string   { return 'app'; }

    public function permissions(): array
    {
        return [DocumentsPermission::class];
    }

    public function roles(): array
    {
        return [EditorRole::class, ViewerRole::class];
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
namespace App\Guards\App\Permissions;

use AzGuard\Contracts\PermissionInterface;
use AzGuard\Attributes\GateAbility;

enum DocumentsPermission: string implements PermissionInterface
{
    #[GateAbility]
    case View   = 'documents.view';
    #[GateAbility]
    case Create = 'documents.create';
    #[GateAbility]
    case Edit   = 'documents.edit';
    #[GateAbility]
    case Delete = 'documents.delete';
}
```

## 5. Create a role and assign it

```php
namespace App\Guards\App\Roles;

use AzGuard\Contracts\RoleInterface;
use App\Guards\App\AppGuard;

class EditorRole implements RoleInterface
{
    public function getName(): string  { return 'editor'; }
    public function getPanel(): string { return 'app'; }
    public function getLevel(): int    { return 10; }

    public function permissions(): array
    {
        return [
            AppGuard::permission(DocumentsPermission::View),
            AppGuard::permission(DocumentsPermission::Create),
            AppGuard::permission(DocumentsPermission::Edit),
        ];
    }
}
```

```php
$user->assignRole(EditorRole::class, panel: 'app');
```

## Verify it works

```php
$user->hasAzPermission(DocumentsPermission::View); // true

Gate::allows('app.documents.view', $document);     // true
```

Run the built-in doctor to confirm your setup:

```bash
php artisan azguard:doctor
```

## Next steps

- [Panels](/guide/panels) — understand app vs admin isolation
- [Permissions](/guide/permissions) — naming conventions, `#[RoleOnly]`, TypeScript export
- [Roles](/guide/roles) — static and custom (DB-backed) roles
- [HTTP Access](/guide/http-access) — `#[CheckPermission]` on controllers
- [Direct Grants](/guide/direct-grants) — per-user permissions without a role
