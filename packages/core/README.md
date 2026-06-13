# AzGuard Core

Code-first role-based access control and permissions for Laravel. Permissions
are declared in PHP (enums or policy attributes), resolved through pluggable
**grant sources** (class roles, database roles, direct grants), and scoped to
**panels** (e.g. `app`, `admin`).

Built on Laravel's own Gate, policies, and middleware — no parallel concepts to
learn.

## Install

```bash
composer require axioma-studio/azguard-core
php artisan vendor:publish --tag=az-guard-config
php artisan migrate
```

## Usage

Add the trait to your user model:

```php
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable
{
    use HasAzGuard;
}
```

Assign roles and check permissions with bare, predictable names:

```php
$user->assignRole('editor');
$user->hasRole('editor');                  // true

$user->hasPermission('app.posts.edit');    // via roles / grants
$user->permissions('app');                 // Collection<string>
$user->flushPermissions();                 // clear the resolved cache
```

It also works through Laravel's Gate and Blade:

```php
Gate::allows('app.posts.edit');

@azcan('app.posts.edit')
    <x-edit-button />
@endazcan
```

### Direct grants

One-off, optionally expiring permissions that bypass roles — one verb set
everywhere (`grant` / `revoke` / `grants`):

```php
use AzGuard\Facades\AzGuard;

AzGuard::forUser($user)->on('app')->ttl(3600)->grant('app.posts.export');
AzGuard::forUser($user)->on('app')->revoke('app.posts.export');
AzGuard::forUser($user)->on('app')->grants();   // active grants
```

## Key concepts

- **Panel** — an authorization scope. Set per request via the `SetCurrentPanel`
  middleware or `azguard.panel_check`.
- **GrantSource** — a pluggable resolver (`ClassRoleGrantSource`,
  `DatabaseRoleGrantSource`, `DirectGrantSource`). The resolver merges them all.
- **PermissionCatalog** — the registry of declared permissions, built from
  enums or `#[GateAbility]` policy attributes.

See the [documentation](https://github.com/axioma-studio/azguard) for panels,
the permission catalog, scoped roles, and the Artisan tooling.

## License

MIT.
