# UUID / ULID Support

AzGuard works with both integer primary keys (default) and string-based keys such as UUID v4 or ULID. No special package configuration is required — just follow Laravel's standard approach.

## UUID model

```php
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use AzGuard\Traits\HasAzGuard;

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
use AzGuard\Traits\HasAzGuard;

class User extends Authenticatable
{
    use HasUlids;
    use HasAzGuard;
}
```

## Migration considerations

When using UUID or ULID keys the `model_has_roles` pivot table's foreign key column must match:

```php
// In the published migration, change:
$table->unsignedBigInteger('user_id');
// to:
$table->uuid('user_id');   // or ulid()
```

AzGuard does not publish migrations separately. To change the column type, publish the config and add a custom migration after `az_guard` migrations run:

```bash
php artisan vendor:publish --tag=az-guard-config
```

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
