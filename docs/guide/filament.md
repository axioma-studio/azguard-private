# Filament Integration

The `axioma-studio/azguard-filament` package provides a first-party UI for
managing roles and direct grants, plus **config-driven, zero-boilerplate
authorization** for your own resources (Filament 5).

## Installation

```bash
composer require axioma-studio/azguard-filament
php artisan vendor:publish --tag=az-guard-filament-config
```

Register the plugin in your Filament panel provider, pointing it at the AzGuard
panel it should manage:

```php
use AzGuard\Filament\AzGuardPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugins([
        AzGuardPlugin::make()->forPanel('admin'),
    ]);
}
```

## How resource permissions work

You do **not** add authorization code to your resources. The plugin discovers
the panel's resources and pages and generates one permission per ability, keyed
`{panel}.{resource}.{ability}` — e.g. `admin.post.view_any`, `admin.post.create`.

Everything is controlled from `config/az-guard-filament.php`: the ability set,
the key scheme, the source, exclusions, and a `super_admin` bypass.

### Sources

- **`database`** (default) — keys are registered in the catalog at runtime and
  appear in the Role UI; grant them to roles in the database. No generated code.
- **`enum`** — generate a typed permission enum per resource:

  ```bash
  php artisan azguard:filament:generate --source=enum
  ```

Preview without writing anything:

```bash
php artisan azguard:filament:generate --dry-run
```

### Enforcement

With `enforce = true` (the default), the plugin makes Filament consult the Gate
for every resource and answers each check from the user's AzGuard permissions.
A user sees and can act on a resource only when they hold the matching
permission — with no base class, trait, or policy in the resource. A role
carrying the `*` wildcard (e.g. a SuperAdmin role) passes every check.

To opt out and manage authorization yourself, set `enforce` to `false`.

## Built-in management resources

### RoleResource

Lists all roles (PHP class roles + custom DB roles) for the configured panel.
You can view class roles read-only, create/edit/delete DB roles, and assign
permissions to DB roles from a picker grouped by permission group.

### DirectGrantResource

Lists direct grants for any user on the panel — create (user + permission +
optional expiry) and revoke.

### Doctor page

The **AzGuard Doctor** page is the GUI equivalent of `php artisan azguard:doctor`:
it surfaces catalog conflicts, roles referencing unknown permissions, and the
panel → ability → handler map. The navigation badge turns red on errors and
yellow on warnings.

## Configuration

See [`config/az-guard-filament.php`](https://github.com/axioma-studio/azguard)
for the full, commented options: `panel`, `source`, `abilities`, `pages`,
`widgets`, `key`, `case`, `exclude`, `super_admin`, `enforce`, and generation
paths.

## Compatibility

Requires Filament `^5.0`.

## Invariant

The plugin only authorizes against permissions scoped to the panel passed to
`forPanel()`. App-panel roles have no effect inside the Filament admin.
