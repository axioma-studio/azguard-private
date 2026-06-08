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
- **Pick from a permission dropdown** — the picker is populated from all registered permission enums for the panel

### DirectGrantResource

Shows all direct grants for any user on the configured panel. Supports:

- Create a grant (user + permission + optional expiry)
- Edit expiry or notes
- Revoke (soft-delete) a grant
- Filter by user, permission, or status

## Protecting your own resources with GuardResource

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

## Custom roles picker

When editing a custom `AzRole` in `RoleResource`, the permissions tab shows a multi-select populated from all `#[GateAbility]`-annotated enum cases for the panel. Static role permissions are shown read-only for reference.

## Compatibility

| azguard/filament | Filament 4 | Filament 5 |
|---|:---:|:---:|
| `0.x` (dev) | ✅ | ✅ |

## Invariant

The Filament plugin checks only permissions scoped to the panel passed to `forPanel()`. App-panel roles have no effect inside the Filament admin.
