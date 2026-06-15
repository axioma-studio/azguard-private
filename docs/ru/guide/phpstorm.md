# PhpStorm

## Автодополнение enum

PhpStorm автоматически понимает backed-enum'ы прав — IDE предлагает enum-кейсы при вводе `hasPermission(`.

## Laravel Idea

Плагин [Laravel Idea](https://laravel-idea.com/) добавляет:
- Автодополнение для `assignRole()` — предлагает зарегистрированные имена ролей
- Навигацию по ролям: `Ctrl+Click` на имя роли
- Инспекции для проверки существования ролей

## .phpstorm.meta.php

`assignRole()` принимает **имя** роли (строку), поэтому подсказываем именно имена:

```php
// .phpstorm.meta.php
namespace PHPSTORM_META {
    expectedArguments(
        \AzGuard\Concerns\HasRoles::assignRole(),
        0,
        'editor',
        'viewer',
        'admin',
    );
}
```

## Xdebug и проверки прав

При отладке AzGuard через Xdebug установите точку останова в `HasAzGuard::hasPermission()` — это позволит видеть полный стек проверки.
