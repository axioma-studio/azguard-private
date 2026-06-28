# Artisan Commands

AzGuard ships runtime commands under the `guard:` prefix and scaffolding generators under the `make:guard-` prefix.

## Reference

| Command | Description |
|---|---|
| `guard:doctor` | Validate config, migrations, and role definitions |
| `guard:doctor --panel=app` | Scope doctor to a single panel |
| `guard:sync-roles` | Sync static role definitions to the database |
| `guard:cache-reset` | Clear the permission cache |
| `guard:cache-reset --force` | Skip the confirmation prompt |
| `guard:list-permissions` | List all registered permissions across panels |
| `guard:list-permissions app` | Filter by panel |
| `make:guard-permission {Panel} {Domain} {Case?}` | Add a case to (or create) a permission enum |
| `make:guard-role` | Scaffold a new role class (interactive) |
| `make:guard-panel {Panel}` | Scaffold a panel provider |

## `guard:doctor`

Runs a suite of checks and prints a report:

```bash
php artisan guard:doctor
php artisan guard:doctor --panel=app
```

Checks performed:

- Config `panels` array is not empty
- All registered panel provider classes exist
- Required migrations have been run
- Every registered permission enum is a valid backed enum
- Every role class implements `RoleInterface`
- No duplicate resolved permission strings across enums
- All `#[GateAbility]` methods have a corresponding policy class
- Cache store is reachable

## `guard:sync-roles`

Syncs static role metadata (name, panel, level) to the `roles` table. Useful after adding a new static role in CI/CD:

```bash
php artisan guard:sync-roles
```

::: warning
This command does **not** assign roles to users. It only ensures the role record exists so custom UI can reference it.
:::

## `guard:cache-reset`

```bash
php artisan guard:cache-reset          # prompts for confirmation
php artisan guard:cache-reset --force  # skip the confirmation prompt
```

Run this after modifying a role's `permissions()` array in code and deploying, so the new permissions take effect immediately.

## Scaffolding commands

```bash
# Add a case to (or create) a permission enum: panel, domain, case
php artisan make:guard-permission App Invoices View
# → app/Guards/App/Invoices/Permissions/InvoicesPermission.php

# New role class (interactive — prompts for panel and name)
php artisan make:guard-role

# New panel provider
php artisan make:guard-panel Api
# → app/Guards/Api/...
```
