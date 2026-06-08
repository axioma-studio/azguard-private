# Permission Catalog

This page documents all first-party permissions shipped with AzGuard panels. Application-specific permissions are defined in your own enums.

## Admin panel (`admin.*`)

These permissions are registered by `azguard/filament` when `AzGuardPlugin` is installed.

| Permission | Resolved key | Description |
|---|---|---|
| Roles — View | `admin.az-guard.roles.view` | View the role list in Filament |
| Roles — Manage | `admin.az-guard.roles.manage` | Create, edit, delete roles |
| Direct Grants — View | `admin.az-guard.grants.view` | View direct grants list |
| Direct Grants — Manage | `admin.az-guard.grants.manage` | Create, edit, revoke grants |

## App panel (`app.*`)

The core package does not register app-panel permissions. All `app.*` permissions are defined by your application's enums.

## Adding your own

```bash
php artisan azguard:make-permission App InvoicesPermission
```

Then register the class in your `AppPanelProvider::permissions()`.

## Listing registered permissions

```bash
php artisan azguard:list-permissions --panel=app
php artisan azguard:list-permissions --panel=admin
```

Output includes resolved key, enum class, and Gate registration status.
