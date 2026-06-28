# UUID / ULID Support

AzGuard works with both integer primary keys (default) and string-based keys such as UUID v4 or ULID. No special package configuration is required — just follow Laravel's standard approach.

## UUID model

```php
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable
{
    use HasUuids;
    use HasAzGuard;
}
```

AzGuard stores role assignments using the model's `getKey()` method, which returns the correct type regardless of key format.

## ULID model

```php
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable
{
    use HasUlids;
    use HasAzGuard;
}
```

## Migration considerations

When using UUID or ULID keys, tell AzGuard which morph-key type to use so its
migrations create the right column types for `model_has_roles`, `model_has_scopes`,
and `az_direct_grants`. Publish the config and set `column_names.morph_type`:

```bash
php artisan vendor:publish --tag=az-guard-config
```

```php
// config/az-guard.php
'column_names' => [
    'morph_type' => 'ulid',   // 'int' (default), 'ulid', or 'uuid'
],
```

You can also set it via the `AZ_GUARD_MORPH_TYPE` environment variable. AzGuard's
migrations read this value and create the morph-id columns accordingly — no manual
migration editing required.

::: warning
If you change key types on an existing installation, you must migrate the data in `model_has_roles` manually.
:::

## Morph maps

If you use morph maps, register the alias in your `AppServiceProvider`:

```php
Relation::morphMap([
    'user' => \App\Models\User::class,
]);
```

AzGuard resolves morph types through Laravel's `Relation::getMorphedModel()`, so morph maps are respected automatically.
