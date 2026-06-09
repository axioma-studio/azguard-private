# UUID / ULID

AzGuard поддерживает UUID и ULID как первичные ключи для модели User.

## UUID

```php
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Authenticatable
{
    use HasUuids, HasAzGuard;
}
```

Миграция:

```php
// Pivot-таблица azguard_user_roles
Schema::create('azguard_user_roles', function (Blueprint $table) {
    $table->uuid('user_id');
    $table->string('role_class');
    $table->string('panel')->nullable();
    $table->timestamps();

    $table->primary(['user_id', 'role_class', 'panel']);
});
```

## ULID

```php
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class User extends Authenticatable
{
    use HasUlids, HasAzGuard;
}
```

## Morph Map

Если вы используете полиморфные связи:

```php
// app/Providers/AppServiceProvider.php
Relation::morphMap([
    'user' => App\Models\User::class,
]);
```
