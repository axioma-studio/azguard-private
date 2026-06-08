# Artisan-команды

## azguard:doctor

Диагностирует конфигурацию и находит типичные проблемы:

```bash
php artisan azguard:doctor
```

Проверяет:
- Корректность `config/azguard.php`
- Существование классов панелей
- Наличие всех миграций
- Соответствие ролей в БД и PHP-классов
- Наличие трейта `HasAzGuard` на моделях

## azguard:sync-roles

Синхронизирует PHP-классы ролей с таблицей `azguard_user_roles`:

```bash
php artisan azguard:sync-roles
```

Полезно после:
- Переименования классов ролей
- Перемещения классов в другое пространство имён
- Удаления старых ролей

## azguard:prune-grants

Удаляет истёкшие прямые гранты:

```bash
php artisan azguard:prune-grants
```

Добавьте в планировщик:

```php
$schedule->command('azguard:prune-grants')->dailyAt('03:00');
```

## azguard:list-permissions

Выводит все зарегистрированные разрешения:

```bash
php artisan azguard:list-permissions
php artisan azguard:list-permissions --panel=app
php artisan azguard:list-permissions --panel=admin
```

## azguard:list-roles

Выводит все роли с их разрешениями:

```bash
php artisan azguard:list-roles
php artisan azguard:list-roles --role=EditorRole
```
