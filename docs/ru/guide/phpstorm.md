# PhpStorm

## Автодополнение enum-прав

PhpStorm автоматически подсказывает enum-кейсы при вводе `PostsPermission::`.

## Laravel Idea

Плагин [Laravel Idea](https://plugins.jetbrains.com/plugin/13441-laravel-idea) добавляет поддержку:
- Автодополнение в `#[CheckPermission(...)]`
- Переход к объявлению права по Ctrl+Click
- Подсветка несуществующих кейсов

## .phpstorm.meta.php

Для дополнительных подсказок создайте файл:

```php
// .phpstorm.meta.php
namespace PHPSTORM_META {
    override(\AzGuard\Facades\AzGuard::grant(0, 1), map([
        '' => \App\AzGuard\App\Permissions\,
    ]));
}
```

## Xdebug + тесты

```xml
<!-- phpunit.xml -->
<php>
    <env name="XDEBUG_MODE" value="coverage"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
</php>
```
