# Installation

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.2+ |
| Laravel | 11.x or 12.x |
| Database | MySQL 8+, PostgreSQL 13+, SQLite 3.35+ |

## Install via Composer

```bash
composer require azguard/azguard
```

## Publish the config

```bash
php artisan vendor:publish --tag=az-guard-config
```

This creates `config/az-guard.php`. See the [Configuration reference](/guide/configuration) for all options.

## Run migrations

```bash
php artisan migrate
```

Five tables are created:

| Table | Purpose |
|---|---|
| `az_guard_roles` | Dynamic role definitions |
| `az_guard_model_has_roles` | User → role assignments |
| `az_guard_model_has_scopes` | User → scoped role assignments |
| `az_guard_role_permissions` | Dynamic role → permission links |
| `az_guard_direct_grants` | Per-user direct permission grants |

## Add the trait

```php
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable
{
    use HasAzGuard;
}
```

## Optional: publish migrations

If you need to customize the table structure:

```bash
php artisan vendor:publish --tag=az-guard-migrations
```

Then edit the migrations before running `php artisan migrate`.

## Verify installation

```bash
php artisan azguard:doctor
```

Expected output on a fresh install:

```
✓ Config file found
✓ Migrations are up to date
✓ No panels registered yet — add one in config/az-guard.php
```

## Laravel Octane

AzGuard is Octane-safe. The permission resolver uses per-request state and does not share memory between requests. No additional configuration is needed.

## Testing environment

When running tests, use `RefreshDatabase` or the `AzGuardFake` helper:

```php
use AzGuard\Testing\AzGuardFake;

protected function setUp(): void
{
    parent::setUp();
    AzGuardFake::install();  // replaces resolver with an in-memory fake
}
```

See [Testing](/guide/testing) for the full guide.
