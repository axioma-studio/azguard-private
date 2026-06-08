# Prerequisites

Before installing AzGuard, make sure your environment meets the following requirements.

## System requirements

| Requirement | Minimum version |
|---|---|
| PHP | 8.2 |
| Laravel | 10.x, 11.x, 12.x |
| Database | MySQL 8 / PostgreSQL 14 / SQLite 3.35+ |

## PHP extensions

AzGuard uses standard Laravel infrastructure. No uncommon extensions are needed beyond what a default Laravel install provides:

- `ext-pdo` — database access
- `ext-json` — serialization
- `ext-mbstring` — string handling

## The `HasAzGuard` trait

Your `User` model (or any authenticatable model) must use the `HasAzGuard` trait:

```php
use AzGuard\Traits\HasAzGuard;

class User extends Authenticatable
{
    use HasAzGuard;
}
```

### Authorizable contract required

AzGuard integrates with Laravel's `Gate` layer. For `$user->can()`, `$this->authorize()`, and policy checks to work correctly, your User model must implement the `Authorizable` contract. Laravel's default `Authenticatable` base class already includes this — if you use a custom base class, make sure it also extends `Illuminate\Foundation\Auth\User` or manually implements `Illuminate\Contracts\Auth\Access\Authorizable`.

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use AzGuard\Traits\HasAzGuard;

class User extends Authenticatable  // <-- Authorizable is included here
{
    use HasAzGuard;
}
```

::: warning Do not skip this step
If your model doesn't implement `Authorizable`, calls to `$user->can()` and `$this->authorize()` will either throw errors or always return `false`.
:::

## Reserved property and relation names

The `HasAzGuard` trait defines several methods and relationships on your model. If your model — or any of its traits — defines any of the following, you will get unexpected behavior or fatal errors:

**Do not define these as database columns, model properties, Eloquent relations, or methods:**

| Name | Why it's reserved |
|---|---|
| `roles` | Used internally by `HasAzGuard` for role resolution |
| `permissions` | Used internally for permission collection access |
| `hasRole` | Method defined by trait |
| `hasPermission` | Method defined by trait |
| `assignRole` | Method defined by trait |
| `removeRole` | Method defined by trait |
| `syncRoles` | Method defined by trait |
| `getRoles` | Method defined by trait |
| `getAllPermissions` | Method defined by trait |

If you have an existing `roles` or `permissions` column on your users table (e.g., a legacy JSON column), you must rename it before installing AzGuard.

## Configuration file

AzGuard publishes `config/azguard.php` during installation. If a file with that name already exists in your project, the installer will skip publishing it. In that case, run:

```bash
php artisan vendor:publish --tag=azguard-config --force
```

Then manually merge any custom values from your old file.

## Database schema

AzGuard creates several tables prefixed with `az_guard_`. By default these use standard `unsignedBigInteger` foreign keys referencing your `users.id` column.

### MySQL: key length limitation

On MySQL with `utf8mb4` charset, you may see:

```
Illuminate\Database\QueryException: Specified key was too long;
max key length is 767 bytes
```

Fix options (choose one):

**Option A — Set InnoDB row format (recommended):**
```php
// AppServiceProvider::boot()
use Illuminate\Support\Facades\Schema;

Schema::defaultStringLength(191);
```

**Option B — Add to `config/database.php`:**
```php
'mysql' => [
    // ...
    'engine' => 'InnoDB ROW_FORMAT=DYNAMIC',
]
```

**Option C — Use MariaDB 10.3+ or MySQL 8.0.17+**, which use Dynamic row format by default.

### Foreign keys

AzGuard migrations include foreign key constraints to `users.id` by default. If you are running without foreign key support (e.g., SQLite in testing), this is handled automatically by the migrations via a config flag:

```php
// config/azguard.php
'use_foreign_keys' => env('AZGUARD_FK', true),
```

Set `AZGUARD_FK=false` in your `.env` for environments where FK constraints are not supported.

When a user is deleted from `users`, AzGuard pivot records (role assignments, direct grants) are **not** automatically cascade-deleted unless foreign keys are enabled. With foreign keys on, the `ON DELETE CASCADE` constraint handles cleanup automatically. Without foreign keys, call:

```php
AzGuard::forUser($user)->cleanup();
```
...before deleting the user, or handle cleanup in an `Eloquent::deleting` observer.

## UUID / ULID primary keys

By default, AzGuard assumes integer (`bigint`) primary keys on your users table. If you use **UUID, ULID, or any other string-based primary key**, you must adjust both the migrations and the config before running `migrate`.

See [UUID / ULID Support](./uuid-ulid.md) for a step-by-step guide.

::: danger Run before migrate
Changing the key type after migration requires dropping and recreating AzGuard tables. Always configure this before the first `php artisan migrate`.
:::

## Multi-guard apps

If your application uses multiple [guards](https://laravel.com/docs/authentication#introduction) (e.g., `web` and `api`), each guard can have its own independent set of permissions and roles. See [Multiple Guards](./multiple-guards.md) for setup instructions.

## Octane compatibility

AzGuard is stateless by design. There are no static caches that survive between requests, so it works safely with [Laravel Octane](https://laravel.com/docs/octane) (Swoole & RoadRunner) out of the box.
