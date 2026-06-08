# Требования

## Версии

| Зависимость | Версия |
|---|---|
| PHP | ≥ 8.2 |
| Laravel | 11.x или 12.x |
| Composer | 2.x |

## PHP-расширения

AzGuard не требует специфических расширений — достаточно стандартного набора Laravel:

- `mbstring`
- `pdo` + нужный PDO-драйвер (`pdo_mysql`, `pdo_pgsql`, и т.д.)
- `openssl`

## Laravel Octane

AzGuard **совместим с Octane** (Swoole и RoadRunner). Пакет не хранит глобального состояния между запросами — каждая проверка разрешения работает с данными конкретного запроса.

::: warning
Если вы используете `AzGuardFacade` в статических инициализаторах (например, в `AppServiceProvider::boot()`) — убедитесь, что контекст не разделяется между воркерами.
:::

## Модель User

Ваша модель User (или любая аутентифицируемая модель) должна использовать трейт `HasAzGuard`:

```php
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable
{
    use HasAzGuard;
}
```

Трейт добавляет методы:
- `hasPermission(PermissionInterface|string $permission): bool`
- `hasRole(string $role): bool`
- `assignRole(string $roleClass): void`
- `revokeRole(string $roleClass): void`
- `grantPermission(PermissionInterface $permission, ?Carbon $expiresAt = null): void`
