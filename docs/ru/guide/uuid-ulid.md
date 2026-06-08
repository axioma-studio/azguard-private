# UUID и ULID

AzGuard поддерживает модели с UUID и ULID первичными ключами без дополнительной конфигурации.

## UUID

```php
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable
{
    use HasUuids, HasAzGuard;
}
```

Миграция pivot-таблицы для UUID:

```php
Schema::create('azguard_user_roles', function (Blueprint $table) {
    $table->uuid('user_id');
    $table->string('role_class');
    $table->string('panel')->default('app');
    $table->timestamps();
    $table->primary(['user_id', 'role_class', 'panel']);
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
});
```

## ULID

```php
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable
{
    use HasUlids, HasAzGuard;
}
```

## Morph Maps

При использовании полиморфных таблиц зарегистрируйте morph map:

```php
// AppServiceProvider
Relation::morphMap([
    'user' => App\Models\User::class,
]);
```
