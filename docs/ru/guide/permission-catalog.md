# Каталог разрешений

Каталог — это полный реестр всех enum-разрешений, зарегистрированных в AzGuard.

## Просмотр

```bash
# Все разрешения
php artisan azguard:list-permissions

# По панели
php artisan azguard:list-permissions --panel=admin

# В формате JSON (для экспорта)
php artisan azguard:list-permissions --json
```

## Программный доступ

```php
use AzGuard\Facades\AzGuard;

// Все зарегистрированные разрешения
$permissions = AzGuard::permissions(); // Collection

// По панели
$adminPerms = AzGuard::permissions(panel: 'admin');

// Проверить, существует ли разрешение
AzGuard::hasPermission('app.posts.edit'); // bool
```

## Рекомендации по структуре каталога

```php
// Единый файл-реестр для документирования
// app/AzGuard/PermissionCatalog.php

class PermissionCatalog
{
    /**
     * Возвращает все enum-классы разрешений приложения.
     * @return class-string<PermissionInterface>[]
     */
    public static function all(): array
    {
        return [
            PostsPermission::class,
            CommentsPermission::class,
            UsersPermission::class,
            // ...
        ];
    }
}
```
