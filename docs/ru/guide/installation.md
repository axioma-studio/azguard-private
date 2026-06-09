# Установка

## Требования

| Зависимость | Версия |
|---|---|
| PHP | ≥ 8.2 |
| Laravel | 11.x или 12.x |
| Laravel Octane | совместим (stateless) |

## Установка через Composer

```bash
composer require axioma-studio/azguard
```

## Публикация конфигурации

```bash
php artisan vendor:publish --tag=azguard-config
```

Это создаст `config/azguard.php`.

## Публикация и запуск миграций

```bash
php artisan vendor:publish --tag=azguard-migrations
php artisan migrate
```

Миграции создадут таблицы:
- `azguard_user_roles` — связки user → role
- `azguard_direct_grants` — прямые гранты с TTL

## Добавление трейта

Добавьте `HasAzGuard` в вашу модель User (или любую другую модель, которой нужны роли):

```php
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable
{
    use HasAzGuard;
}
```

## Конфигурация панелей

В `config/azguard.php` укажите панели вашего приложения:

```php
return [
    'panels' => [
        'app'   => App\AzGuard\App\AppPanel::class,
        'admin' => App\AzGuard\Admin\AdminPanel::class,
        'api'   => App\AzGuard\Api\ApiPanel::class,
    ],
];
```

::: tip Octane
AzGuard не хранит глобального состояния. Работает с Laravel Octane и Kubernetes без дополнительной настройки.
:::
