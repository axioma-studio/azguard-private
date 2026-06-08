# Permission Catalog

The **permission catalog** is the single source of truth for every valid permission key in your application. AzGuard builds it at boot time by scanning all registered `PermissionInterface` enums for each panel.

## How it works

When a request comes in, AzGuard checks the permission key against the catalog:

- If the key **is in the catalog** → AzGuard resolves it (from roles, direct grants, wildcard)
- If the key **is not in the catalog** → AzGuard returns `null` to Laravel Gate, allowing other hooks to handle it

This means unknown permission keys are never silently denied — they pass through to your other Gate hooks.

## First-party permissions (azguard/filament)

These permissions are registered automatically when `AzGuardPlugin` is installed.

### Admin panel (`admin.*`)

| Permission key | Enum case | Description |
|---|---|---|
| `admin.az-guard.roles.view` | `AzGuardPermission::RolesView` | View the role list in Filament |
| `admin.az-guard.roles.manage` | `AzGuardPermission::RolesManage` | Create, edit, delete roles |
| `admin.az-guard.grants.view` | `AzGuardPermission::GrantsView` | View direct grants list |
| `admin.az-guard.grants.manage` | `AzGuardPermission::GrantsManage` | Create, edit, revoke grants |
| `admin.az-guard.doctor.view` | `AzGuardPermission::DoctorView` | Access the AzGuard Doctor page |

### App panel (`app.*`)

The core package does not register any `app.*` permissions. All `app.*` keys come from your application's own enums.

## Defining your own catalog

```bash
php artisan azguard:make-permission App InvoicesPermission
```

Then register the class in your `AppPanelProvider::permissions()`:

```php
class AppPanelProvider implements PanelProviderInterface
{
    public function permissions(): array
    {
        return [
            DocumentsPermission::class,
            InvoicesPermission::class,  // <-- add here
        ];
    }
}
```

## Listing the catalog

```bash
# All permissions for all panels
php artisan azguard:list-permissions

# Filtered by panel
php artisan azguard:list-permissions --panel=app
php artisan azguard:list-permissions --panel=admin

# With Gate registration status
php artisan azguard:list-permissions --panel=app --verbose
```

Example output:

```
Panel: app
┌─────────────────────────────────┬───────────────────────────────────┬────────┐
│ Key                             │ Enum class                        │ Gate   │
├─────────────────────────────────┼───────────────────────────────────┼────────┤
│ app.documents.view              │ DocumentsPermission::View         │ ✓      │
│ app.documents.create            │ DocumentsPermission::Create       │ ✓      │
│ app.documents.edit              │ DocumentsPermission::Edit         │ ✓      │
│ app.documents.delete            │ DocumentsPermission::Delete       │ ✓      │
│ app.invoices.view               │ InvoicesPermission::View          │ ✓      │
│ app.invoices.export             │ InvoicesPermission::Export        │ ✗      │
└─────────────────────────────────┴───────────────────────────────────┴────────┘
```

`✗` in the Gate column means the case is marked `#[RoleOnly]` — it is not registered as a Gate ability and can only be checked via `$user->hasPermission()`.

## Validating the catalog

```bash
php artisan azguard:doctor
```

The doctor reports:
- Enum cases that are registered in the catalog but missing from the database (if you use DB-backed roles)
- DB permission keys that no longer exist in any enum (orphans after a rename)
- Panels with zero registered permissions
- Role classes that reference permissions not in the catalog

## Refreshing the catalog cache

The catalog is cached at boot time. After adding new permissions, run:

```bash
php artisan azguard:cache-flush
# or clear all caches:
php artisan cache:clear
```

In production, the catalog is rebuilt on the next request after `cache:clear`.
