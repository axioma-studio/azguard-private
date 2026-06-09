# Artisan Commands

AzGuard ships commands under several prefixes: `azguard:`, `guard:`, and `make:guard-` for scaffolding.

## Reference

| Command | Description |
|---|---|
| `azguard:doctor` | Validate config, migrations, and role definitions |
| `azguard:doctor --panel=app` | Scope doctor to a single panel |
| `azguard:sync-roles` | Sync static role definitions to the database |
| `azguard:cache-reset` | Clear the per-user permission cache |
| `azguard:cache-reset --user=42` | Clear cache for a specific user ID |
| `azguard:list-permissions` | List all registered permissions across panels |
| `azguard:list-permissions --panel=app` | Filter by panel |
| `make:guard-permission {Panel} {Name}` | Scaffold a new permission enum |
| `make:guard-role {Panel} {Name}` | Scaffold a new role class |
| `make:guard-panel {Name}` | Scaffold a panel provider |

## `azguard:doctor`

Runs a suite of checks and prints a report:

```bash
php artisan azguard:doctor
php artisan azguard:doctor --panel=app
```

Checks performed:

- Config `panels` array is not empty
- All registered panel provider classes exist
- Required migrations have been run
- Every permission enum implements `PermissionInterface`
- Every role class implements `RoleInterface`
- No duplicate resolved permission strings across enums
- All `#[GateAbility]` methods have a corresponding policy class
- Cache store is reachable

## `azguard:sync-roles`

Syncs static role metadata (name, panel, level) to the `roles` table. Useful after adding a new static role in CI/CD:

```bash
php artisan azguard:sync-roles
```

::: warning
This command does **not** assign roles to users. It only ensures the role record exists so custom UI can reference it.
:::

## `azguard:cache-reset`

```bash
php artisan azguard:cache-reset            # all users
php artisan azguard:cache-reset --user=42  # single user
```

Run this after modifying a role's `permissions()` array in code and deploying, so the new permissions take effect immediately.

## Scaffolding commands

```bash
# New permission enum
php artisan make:guard-permission App InvoicesPermission
# → app/Guards/App/Permissions/InvoicesPermission.php

# New role class
php artisan make:guard-role App AccountantRole
# → app/Guards/App/Roles/AccountantRole.php

# New panel provider
php artisan make:guard-panel Api
# → app/Guards/Api/ApiPanelProvider.php
```
