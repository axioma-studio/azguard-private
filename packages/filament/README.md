# AzGuard for Filament

Config-driven, zero-boilerplate resource permissions for [Filament 5](https://filamentphp.com),
plus a UI for managing roles and direct grants — powered by
[`axioma-studio/azguard-core`](../core).

You declare **how** permissions are shaped once, in config; the plugin discovers
your panel's resources, registers the permission schema, and enforces it. Your
Filament resources need **no authorization code**.

## Install

```bash
composer require axioma-studio/azguard-filament
php artisan vendor:publish --tag=az-guard-filament-config
```

Register the plugin on the Filament panel that AzGuard should guard, and point it
at the matching AzGuard panel id:

```php
use AzGuard\Filament\AzGuardPlugin;

$panel->plugins([
    AzGuardPlugin::make()->forPanel('admin'),
]);
```

## How permissions work

Every discovered resource gets one permission per ability, keyed
`{panel}.{resource}.{ability}` — e.g. `admin.post.view_any`, `admin.post.create`.
The ability set, key scheme, source, and exclusions all live in
`config/az-guard-filament.php`.

### Sources

- **`database`** (default) — keys are registered in the catalog at runtime and
  show up in the Role UI. Grant them to roles in the database; nothing to
  generate.
- **`enum`** — generate a typed permission enum per resource:

  ```bash
  php artisan azguard:filament:generate --source=enum
  ```

Preview the schema (or the database keys) without writing anything:

```bash
php artisan azguard:filament:generate --dry-run
```

### Enforcement

With `enforce = true` (default), the plugin makes Filament consult the Gate for
every resource and answers each check from the user's AzGuard permissions — so a
user sees and can act on a resource only when they hold the matching permission.
No base class, trait, or policy required in your resources.

A role carrying the `*` wildcard (e.g. a SuperAdmin role) passes every check.

## Management UI

The plugin registers resources to manage **Roles** and **Direct grants**, and a
**Doctor** page that surfaces permission/role inconsistencies.

## Configuration

See [`config/az-guard-filament.php`](config/az-guard-filament.php) for the full,
commented set of options: `panel`, `source`, `abilities`, `pages`, `key`,
`case`, `exclude`, `super_admin`, `enforce`, and code-generation paths.
