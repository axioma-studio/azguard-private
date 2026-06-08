# Artisan-команды

## azguard:doctor

Диагностика конфигурации и целостности данных:

```bash
php artisan azguard:doctor
```

Проверяет:
- Корректность `config/azguard.php`
- Наличие всех классов ролей из БД
- Устаревшие записи в `azguard_user_roles`
- Правильность регистрации панелей

## azguard:sync-roles

Синхронизирует PHP-классы ролей с записями в базе данных:

```bash
php artisan azguard:sync-roles

# Только для определённой панели
php artisan azguard:sync-roles --panel=admin

# Dry run — показать что изменится без применения
php artisan azguard:sync-roles --dry-run
```

## azguard:prune-grants

Удаляет просроченные прямые гранты:

```bash
php artisan azguard:prune-grants

# С подтверждением количества удалённых записей
php artisan azguard:prune-grants -v
```

Добавьте в планировщик в `bootstrap/app.php`:

```php
$schedule->command('azguard:prune-grants')->daily();
```

## azguard:list-permissions

Выводит все зарегистрированные разрешения:

```bash
php artisan azguard:list-permissions
php artisan azguard:list-permissions --panel=app
```
