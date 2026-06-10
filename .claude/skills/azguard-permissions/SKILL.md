# azguard-permissions

Use when working with the AzGuard permission system: Panel, GrantSource, PermissionCatalog, Authorizer.

## Core Concepts

### Panel
Authorization scope — every permission check happens within a Panel context.

- Defined by implementing `Support\Panel` (or extending `PanelProvider`)
- Registered via `PanelManager::registerPanel()`
- Set per-request via `SetCurrentPanel` middleware or `azguard.panel_check` combined middleware
- Retrieved via `AzGuard::currentPanel()`

### GrantSource
Pluggable resolver that decides whether a user has a permission.

- `ClassRoleGrantSource` — role assigned as a PHP class
- `DatabaseRoleGrantSource` — role stored in DB via `Role` model
- `DirectGrantSource` — per-user direct permission grants (`DirectGrant` model)
- Custom: implement `Registry\Contracts\GrantSource`, register in config `grant_sources`

### PermissionCatalog
Registry of all declared permissions in a Panel.

- Built from PHP enums via `EnumPermissionCatalogBuilder`
- Built from policy `#[GateAbility]` attributes via `PolicyAbilityCatalogBuilder`
- Composed via `CompositePermissionCatalog`

### Authorizer
`Guard\Authorizer` — resolves final permission by iterating GrantSources in priority order.

## Common Tasks

**Add a new Panel:**
1. Create a class implementing `Panel` (or use `PanelProvider`)
2. Register in `AzGuardServiceProvider` or via `PanelManager::registerPanel()`
3. Add permissions enum and register with catalog builder

**Add a custom GrantSource:**
1. Implement `Registry\Contracts\GrantSource`
2. Add to config `azguard.grant_sources` array
3. Set appropriate `GrantPriority`

**Check permissions in code:**
```php
AzGuard::check($permission);            // current panel
AzGuard::panel('admin')->check($perm);  // specific panel
Gate::allows('panel.permission');        // via Gate integration
```

**Middleware:**
- `azguard.check` — `CheckAccess` middleware
- `azguard.panel_check` — combined SetCurrentPanel + CheckAccess
- `azguard.load_roles` — preloads roles into context

## Do Not

- Bypass `Authorizer` by querying `DirectGrant` model directly for permission checks.
- Register a `GrantSource` outside of config — always use the config allowlist.
- Hardcode Panel names as strings in multiple places — use a constant or enum.
