# PhpStorm

## Автодополнение enum

PhpStorm автоматически понимает `PermissionInterface` — IDE предлагает enum-кейсы при вводе `hasPermission(`.

## Laravel Idea

Плагин [Laravel Idea](https://laravel-idea.com/) добавляет:
- Автодополнение для `assignRole()` — предлагает зарегистрированные классы ролей
- Навигацию по ролям: `Ctrl+Click` на имя роли
- Инспекции для проверки существования ролей

## .phpstorm.meta.php

```php
// .phpstorm.meta.php
namespace PHPSTORM_META {
    expectedArguments(
        \AzGuard\Concerns\HasAzGuard::assignRole(),
        0,
        \App\AzGuard\App\Roles\EditorRole::class,
        \App\AzGuard\App\Roles\ViewerRole::class,
        \App\AzGuard\Admin\Roles\AdminRole::class,
    );
}
```

## Xdebug и проверки прав

При отладке AzGuard через Xdebug установите точку останова в `HasAzGuard::hasPermission()` — это позволит видеть полный стек проверки.
