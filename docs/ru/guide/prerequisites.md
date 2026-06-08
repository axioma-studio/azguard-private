# Требования

## Системные требования

| Зависимость | Версия |
|---|---|
| PHP | ≥ 8.2 |
| Laravel | 11.x или 12.x |
| Composer | ≥ 2.0 |
| Laravel Octane | совместим (stateless) |

## Необходимые расширения PHP

```bash
extension=pdo
extension=pdo_mysql   # или pdo_pgsql / pdo_sqlite
extension=mbstring
```

## Трейт HasAzGuard

Добавьте трейт в вашу модель User (или любую другую модель, которой нужны роли):

```php
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable
{
    use HasAzGuard;
}
```

::: tip Octane
AzGuard не хранит глобального состояния. Полностью совместим с Laravel Octane и Kubernetes.
:::
