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

Your `User` model (or any authenticatable model) must use the `HasAzGuard` trait. This is the only model-level requirement:

```php
use AzGuard\Traits\HasAzGuard;

class User extends Authenticatable
{
    use HasAzGuard;
}
```

## Multi-guard apps

If your application uses multiple [guards](https://laravel.com/docs/authentication#introduction) (e.g. `web` and `api`), each guard can have its own independent set of permissions and roles. See [Multiple Guards](./multiple-guards.md) for setup instructions.

## Octane compatibility

AzGuard is stateless by design. There are no static caches that survive between requests, so it works safely with [Laravel Octane](https://laravel.com/docs/octane) (Swoole & RoadRunner) out of the box.
