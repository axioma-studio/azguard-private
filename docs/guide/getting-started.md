# Быстрый старт

## Установка ядра
```bash
composer require azguard/azguard
```

## Настройка модели User
Добавьте трейт `HasAzGuard`:
```php
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable {
    use HasAzGuard;
}
```
