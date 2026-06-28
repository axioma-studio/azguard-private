# Upgrading

## To 1.0 (enum/class-first)

AzGuard 1.0 makes the public API **enum- and class-first**. The string forms still
work where they always did (Blade, the Gate, config), so most upgrades are
mechanical. Highlights:

### Roles declare permissions as enum cases

`RoleInterface::permissions()` / `BaseRole::permissions()` now return
`list<UnitEnum|string>`. Prefer enum cases — the owning panel scopes them
automatically, so you no longer hard-code the `"{panel}."` prefix.

```php
// before
public function permissions(): array
{
    return ['app.documents.view', 'app.documents.create'];
}

// after (preferred — refactor-safe)
public function permissions(): array
{
    return [DocumentsPermission::View, DocumentsPermission::Create];
}
```

Full string keys still work, so this change is backward-compatible. For an enum
case to be granted, its enum must be registered on the panel via
`->permissionEnums([...])` (the `make:guard-panel` scaffold now wires this for you).

### Roles resolve and check by class-string

`assignRole(EditorRole::class)` and `hasRole(EditorRole::class)` now work as
intended — a role class-string resolves by its stored `class_name` (falling back
to the derived name). Previously a class-string was looked up as a literal role
*name* and silently failed. Plain role names still work.

### Panels can be referenced by a backed enum

`panel()`, `permission()`, `tryPermission()`, `grant()`, `revoke()`, `grants()`
and `GrantBuilder::on()` accept `string|BackedEnum`. You can use a typed panel
identifier instead of the `'admin'` magic string. Strings still work everywhere.

### Class-based permissions (new)

For open / multi-module permission sets, implement `AzGuard\Contracts\Permission`
(`static ability(): string`) and reference it by `::class` anywhere a permission
is accepted — an alternative to enums when a closed set would be too rigid.

### `hasAzPermission()` is gone

The removed `hasAzPermission()` method no longer appears anywhere. Two orphaned
policy stubs that still generated it were deleted; use `hasPermission()` (or the
generated `AuthorizesPermission`-based policies). The active `make:guard-policy`
scaffold was already enum-first.

### New console commands

- `php artisan azguard:install` — guided install (publish config + migrate).
- `php artisan azguard:super-admin --user=1` — promote a user to super-admin.
- `php artisan make:guard-panel` now **auto-registers** the panel in
  `config/az-guard.php` and scaffolds an enum-first role + a provider that wires
  the permission enum.

---

## Enum SemVer policy

Permission/role/panel enums you ship are part of your public contract. Because
PHP enums are closed (consumers cannot add cases), treat them under SemVer:

- **Adding** a case → **minor**.
- **Removing or renaming** a case → **major**, with a documented data migration
  (existing DB rows that reference the old value must be migrated).

Store ability/role names as `varchar` (never a SQL `ENUM` column) and resolve
persisted values with `tryFrom()` + a fallback, so a removed/renamed case never
crashes a read of stale data.
