# Panels

A **panel** is an isolated permission namespace. AzGuard ships with two panels by default — `app` (Inertia / API) and `admin` (Filament) — but you can define as many as you need.

## Why panels?

Without namespacing, `posts.edit` in the customer-facing app and `posts.edit` in the backoffice are the same string. AzGuard prefixes every permission with its panel:

| Raw case value | Panel | Resolved permission |
|---|---|---|
| `posts.edit` | `app` | `app.posts.edit` |
| `posts.edit` | `admin` | `admin.posts.edit` |

Roles, permission checks, and Gate definitions all work on the **resolved** string. Roles from one panel never bleed into another.

## Directory layout

```
app/Guards/
├── App/
│   ├── AppGuard.php                 # helper: AppGuard::permission($enum)
│   ├── AppPanelProvider.php
│   ├── Documents/
│   │   ├── Permissions/DocumentsPermission.php
│   │   ├── Policies/DocumentsPolicy.php
│   │   └── Abilities/DocumentsAbilities.php
│   └── Roles/
│       ├── EditorRole.php
│       └── ViewerRole.php
└── Admin/
    ├── AdminGuard.php
    ├── AdminPanelProvider.php
    └── Roles/
        └── SuperAdminRole.php
```

Mirror your `App\Models\{Domain}\` structure under `app/Guards/{Panel}/{Domain}/`.

## Defining a panel

```php
// app/Guards/App/AppPanelProvider.php
use AzGuard\Contracts\PanelProviderInterface;

class AppPanelProvider implements PanelProviderInterface
{
    public function panel(): string { return 'app'; }

    public function permissions(): array
    {
        return [
            DocumentsPermission::class,
            ProjectsPermission::class,
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

Register in `config/az-guard.php`:

```php
'panels' => [
    \App\Guards\App\AppPanelProvider::class,
    \App\Guards\Admin\AdminPanelProvider::class,
],
```

## Policy autodiscovery

AzGuard auto-discovers policies when the path follows the convention:

```
app/Guards/{Panel}/{Domain}/Policies/{Domain}Policy.php
  → resolves to App\Models\{Domain}\{SingularDomain}
```

Override the model with an explicit attribute:

```php
#[AzGuardPolicy(model: Document::class)]
class DocumentsPolicy { ... }
```

## Panel middleware

Attach the panel to a route group so AzGuard resolves permissions in the right namespace:

```php
Route::middleware(['azguard.panel:app', 'azguard.roles'])
    ->group(function () {
        // app panel routes
    });
```

::: tip Filament
The `admin` panel is consumed by `azguard/filament`. See [Filament integration](/guide/filament) for details.
:::
