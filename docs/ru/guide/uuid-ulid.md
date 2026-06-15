# UUID / ULID

AzGuard поддерживает UUID и ULID как первичные ключи для модели User.
Тип полиморфных ключей в таблицах `model_has_roles`, `model_has_scopes` и
`az_direct_grants` задаётся **в конфиге**, а не вручную в миграциях.

## Настройка типа morph-ключа

```php
// config/az-guard.php
'column_names' => [
    'morph_type' => env('AZ_GUARD_MORPH_TYPE', 'int'), // 'int' | 'uuid' | 'ulid'
],
```

```dotenv
# .env
AZ_GUARD_MORPH_TYPE=uuid
```

Миграции AzGuard читают это значение и создают morph-колонки нужного типа —
дополнительных правок схемы не требуется.

## UUID

```php
use AzGuard\Concerns\HasAzGuard;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasUuids, HasAzGuard;
}
```

## ULID

```php
use AzGuard\Concerns\HasAzGuard;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasUlids, HasAzGuard;
}
```

## Morph Map

Если вы используете полиморфные связи:

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Database\Eloquent\Relations\Relation;

Relation::morphMap([
    'user' => App\Models\User::class,
]);
```
