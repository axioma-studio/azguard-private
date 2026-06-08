# Panels

A **panel** is an isolated permission namespace in AzGuard. Think of it as an independent access control domain within a single Laravel application.

## Why panels?

Most real-world Laravel apps have multiple distinct areas, each needing its own access rules:

| Panel | Users | Example permissions |
|---|---|---|
| `app` | End-users | `app.documents.view`, `app.comments.create` |
| `admin` | Staff/operators | `admin.users.delete`, `admin.reports.export` |
| `api` | API clients | `api.webhooks.create`, `api.data.read` |

Without panels, you'd either put everything in one namespace (collisions, confusion) or manage separate permission systems.

## Panel provider

Every panel is declared as a PHP class:

```php
// app/AzGuard/Panels/AppPanelProvider.php
namespace App\AzGuard\Panels;

use AzGuard\Contracts\PanelProviderInterface;
use App\AzGuard\App\Permissions\DocumentsPermission;
use App\AzGuard\App\Permissions\UsersPermission;
use App\AzGuard\App\Roles\EditorRole;
use App\AzGuard\App\Roles\ViewerRole;

class AppPanelProvider implements PanelProviderInterface
{
    public function panel(): string
    {
        return 'app';
    }

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

## Registering panels

```php
// config/az-guard.php
'panels' => [
    \App\AzGuard\Panels\AppPanelProvider::class,
    \App\AzGuard\Panels\AdminPanelProvider::class,
],
```

## How keys are constructed

When a permission `'documents.view'` is registered in the `app` panel, AzGuard stores and checks it as `app.documents.view`. This happens automatically — you never write the prefix in the enum:

```php
// In the enum
case View = 'documents.view';

// Full Gate key (constructed by AzGuard)
// 'app.documents.view'

// Usage
Gate::allows('app.documents.view');
$user->hasPermission('app.documents.view');
```

## Same user, multiple panels

A single user can have completely different roles in different panels:

```php
// User is editor in app, but read-only in admin
$user->assignRole('editor', panel: 'app');
$user->assignRole('viewer', panel: 'admin');

$user->hasPermission('app.documents.edit');   // true
$user->hasPermission('admin.users.delete');   // false
```

## Listing panels

```bash
php artisan azguard:doctor
# Shows all registered panels and their status
```

## Typical project layout

```
app/
  AzGuard/
    Panels/
      AppPanelProvider.php
      AdminPanelProvider.php
    App/
      Permissions/
        DocumentsPermission.php
        UsersPermission.php
      Roles/
        EditorRole.php
        ViewerRole.php
    Admin/
      Permissions/
        UsersPermission.php
      Roles/
        OperatorRole.php
```
