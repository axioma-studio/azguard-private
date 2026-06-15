# Extending

AzGuard is built around contracts and interfaces, making it straightforward to replace or extend its components.

## Custom GrantSource

A `GrantSource` is anything that produces a `PermissionSet` for a user. AzGuard ships with several: `ClassRoleGrantSource` and `DatabaseRoleGrantSource` (read from roles) and `DirectGrantSource` (reads from direct grants). You can add your own:

```php
use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;

class SubscriptionGrantSource implements GrantSource
{
    public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet
    {
        if ($user->subscription?->isPremium()) {
            return PermissionSet::fromKeys([
                'app.reports.export',
                'app.analytics.view',
            ]);
        }

        return PermissionSet::empty();
    }

    public function priority(): int
    {
        // Sources are merged in priority order — higher = resolved first
        return 50;
    }
}
```

Register it in a service provider's `register()` method:

```php
use AzGuard\Facades\AzGuard;

public function register(): void
{
    AzGuard::registerGrantSource(SubscriptionGrantSource::class);
}
```

## Custom permission catalog builder

The catalog builder is responsible for scanning and returning all valid permission definitions for a panel. You can replace it to source permissions from a database, config file, or remote API:

```php
use AzGuard\Registry\Contracts\PermissionCatalogBuilder;

class DatabaseCatalogBuilder implements PermissionCatalogBuilder
{
    public function build(string $panelId): array
    {
        return Permission::where('panel', $panelId)
            ->get()
            ->map(fn ($p) => new GenericPermissionDefinition($p->key))
            ->all();
    }

    public function supports(string $panelId): bool
    {
        return true;
    }
}
```

## Swapping AzGuard models

You can replace any of AzGuard's models with your own subclass via `config/az-guard.php`:

```php
'models' => [
    'role'         => \App\Models\AzGuard\Role::class,         // custom
    'scope'        => \AzGuard\Models\ModelHasScope::class,
    'direct_grant' => \AzGuard\Models\DirectGrant::class,
],
```

```php
// app/Models/AzGuard/Role.php
use AzGuard\Models\Role as BaseRole;

class Role extends BaseRole
{
    // Override as needed, e.g., for UUID foreign keys
    protected $keyType = 'string';
    public $incrementing = false;
}
```

For string-based (UUID/ULID) morph keys, set the morph-type column type in config
instead of subclassing:

```php
'column_names' => [
    'morph_type' => 'ulid',   // 'int' (default), 'ulid', or 'uuid'
],
```

## Custom authorization response

AzGuard passes `null` (not `false`) to Laravel Gate when a permission is not in its catalog, allowing other Gate hooks to handle the check. To customize the denied response:

```php
use Illuminate\Auth\Access\Response;

Gate::after(function ($user, $ability, $result) {
    if ($result === null) {
        return Response::deny('You do not have permission to perform this action.', 403);
    }
});
```
