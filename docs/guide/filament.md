# Filament Integration

The `azguard/filament` package provides a first-party UI for managing roles, permissions, and direct grants from the Filament admin panel.

## Installation

```bash
composer require azguard/filament
```

Register the plugin in your Filament Panel Provider:

```php
use AzGuard\Filament\AzGuardPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            AzGuardPlugin::make()->forPanel('admin'),
        ]);
}
```

`forPanel('admin')` tells the plugin which AzGuard panel this Filament instance manages. Defaults to `'app'`.

## Built-in resources

### RoleResource

Lists all roles (static + custom DB-backed) for the configured panel. From this resource you can:

- **View** static roles (read-only — they are PHP classes)
- **Create / edit / delete** custom `AzRole` records
- **Assign permissions** to custom roles via the Permissions relation manager
- **Pick from a permission dropdown** — populated from all registered permission enums for the panel

### DirectGrantResource

Shows all direct grants for any user on the configured panel. Supports:

- Create a grant (user + permission + optional expiry)
- Edit expiry or notes
- Revoke (soft-delete) a grant
- Filter by user, permission, or status (active / expired / revoked)

### Doctor Page

The **AzGuard Doctor** page provides a visual diagnostic dashboard — the GUI equivalent of `php artisan azguard:doctor`.

```php
// The page is registered automatically by AzGuardPlugin.
// Navigate to: /admin/az-guard/doctor
```

The page shows:

| Section | What it displays |
|---|---|
| Status badge | ✓ OK / ⚠ Warnings / ✗ Errors at a glance |
| Errors | Misconfigured roles, missing migrations, catalog conflicts |
| Warnings | Unused permissions, roles with no users |
| Abilities | Full table of panel → ability → Policy::method mappings |

The navigation badge turns **red** when there are errors, **yellow** for warnings, and disappears when everything is OK. To protect the page itself:

```php
// config/az-guard.php
'filament' => [
    'doctor_permission' => AzGuardPermission::DoctorView,
],
```

## Protecting your own resources

Inherit from `AzGuardResource` to gate any Filament resource behind an AzGuard permission:

```php
use AzGuard\Filament\Resources\AzGuardResource;

final class UserResource extends AzGuardResource
{
    protected static function guardPanel(): string
    {
        return 'admin';
    }

    protected static function viewPermission(): UnitEnum
    {
        return AdminPermission::UsersManage;
    }
}
```

`canViewAny()`, `canCreate()`, `canEdit()`, and `canDelete()` are all proxied through `Gate::allows()` + `AzGuard::permission()` automatically.

## Navigating between permissions and the UI

The RoleResource permission picker is grouped by **permission group** (the first segment of the key, e.g. `documents`, `invoices`). To control grouping, use a `#[Group]` attribute on your enum:

```php
#[Group('Invoices')]
enum InvoicesPermission: string implements PermissionInterface
{
    #[GateAbility]
    case View   = 'invoices.view';
    #[GateAbility]
    case Export = 'invoices.export';
}
```

## Custom roles picker

When editing a custom `AzRole`, the permissions tab shows a multi-select populated from all `#[GateAbility]`-annotated enum cases for the panel. Static role permissions are shown read-only for reference.

## User label column

The DirectGrant user picker searches by `name` by default. To change the label column:

```php
// config/az-guard.php
'filament' => [
    'user_label_column' => 'email',
],
```

## Compatibility

| azguard/filament | Filament 4 | Filament 5 |
|---|:---:|:---:|
| `0.x` (dev) | ✅ | ✅ |

## Invariant

The Filament plugin checks only permissions scoped to the panel passed to `forPanel()`. App-panel roles have no effect inside the Filament admin.
