# Prerequisites

Before installing AzGuard, make sure your environment meets the following requirements.

## System requirements

| Requirement | Minimum version |
|---|---|
| PHP | 8.2 |
| Laravel | 10.x or 11.x |
| Database | MySQL 8 / PostgreSQL 14 / SQLite 3.35+ |

## PHP extensions

AzGuard uses standard Laravel infrastructure. No uncommon extensions are needed beyond what a default Laravel install provides:

- `ext-pdo` — database access
- `ext-json` — serialization
- `ext-mbstring` — string handling

## The `HasAzGuard` trait

Your `User` model (or any authenticatable model) must use the `HasAzGuard` trait **and** implement the `Authorizable` contract (which `Illuminate\Foundation\Auth\User` already does). Without `Authorizable`, Laravel's `can()`, `authorize()`, and `@can` will not work:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use AzGuard\Traits\HasAzGuard;

class User extends Authenticatable
{
    use HasAzGuard;

    // ...
}
```

::: warning Reserved property / method names
Do **not** define any of the following on your User model (or any model that uses `HasAzGuard`). These names are used internally by the trait, and overriding them will break AzGuard:

- `role` — as a property, database column, relation, or method
- `roles` — as a property, database column, relation, or method
- `permission` — as a property, database column, relation, or method
- `permissions` — as a property, database column, relation, or method

If your existing model already has any of these, rename the conflicting member before installing AzGuard.
:::

## Config file

AzGuard publishes `config/azguard.php` during installation. If you already have a file with that name (from a previous installation or a custom config), you must merge them manually:

```bash
# Publish without overwriting:
php artisan vendor:publish --tag=azguard-config

# If the file already exists, use --force to overwrite (backup first):
php artisan vendor:publish --tag=azguard-config --force
```

See [Configuration](./configuration.md) for all available options.

## Database schema requirements

AzGuard creates several tables during migration. A common issue on **MySQL with `utf8mb4` charset** is:

```
PDOException: SQLSTATE[42000]: Syntax error or access violation:
1071 Specified key was too long; max key length is 767 bytes
```

This happens when the default InnoDB row format is `COMPACT` instead of `DYNAMIC`. Fix it with one of these approaches:

**Option A — Set InnoDB row format to DYNAMIC (recommended):**

Add this to your `AppServiceProvider::boot()`:

```php
use Illuminate\Support\Facades\Schema;

public function boot(): void
{
    Schema::defaultStringLength(191);
}
```

**Option B — Use MySQL 8+ with `innodb_default_row_format=DYNAMIC`:**

Set `innodb_default_row_format = DYNAMIC` in `my.cnf`. Modern MySQL 8 installs usually have this by default.

**Option C — Reduce string lengths in AzGuard migrations:**

Publish the migrations and manually reduce the `name` column length from `255` to `125`:

```bash
php artisan vendor:publish --tag=azguard-migrations
```

Then edit the published migration files before running `php artisan migrate`.

::: tip PostgreSQL and SQLite
Neither PostgreSQL nor SQLite has this limitation. The issue only affects MySQL/MariaDB.
:::

## UUID / ULID / GUID primary keys

By default, AzGuard assumes integer (`bigint`) primary keys on all models. If your `users` table (or any other model using `HasAzGuard`) uses string-based primary keys (UUID, ULID, NANOID, etc.), you must update the published migrations and the `azguard.php` config before running `php artisan migrate`.

See [UUID / ULID](./uuid-ulid.md) for the full setup guide.

## Foreign key constraints

AzGuard's pivot tables (`az_guard_user_roles`, `az_guard_role_permissions`, etc.) include foreign key constraints by default. If your setup does not support foreign keys (e.g., MyISAM tables, some SQLite configurations), you can disable them in the published migration files.

When a user or role is deleted via AzGuard's provided methods (`removeRole()`, `syncRoles([])`, `DynamicRole::delete()`), pivot records are cleaned up automatically. However, if you delete records directly via Eloquent (e.g., `$user->delete()`), you must either:

- Rely on `ON DELETE CASCADE` from the foreign key constraints, or
- Call `$user->syncRoles([])` before deleting.

## Multi-guard apps

If your application uses multiple [guards](https://laravel.com/docs/authentication#introduction) (e.g., `web` and `api`), each guard can have its own independent set of permissions and roles. See [Multiple Guards](./multiple-guards.md) for setup instructions.

## Octane compatibility

AzGuard is stateless by design. There are no static caches that survive between requests, so it works safely with [Laravel Octane](https://laravel.com/docs/octane) (Swoole & RoadRunner) out of the box.
