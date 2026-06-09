# Artisan-команды

AzGuard предоставляет несколько Artisan-команд для синхронизации, диагностики и обслуживания.

## `azguard:sync-roles`

Синхронизирует PHP-классы ролей с таблицей `roles` в БД.

```bash
php artisan azguard:sync-roles

# С выводом деталей
php artisan azguard:sync-roles --verbose
```

Запускайте при деплое или в миграциях.

## `azguard:doctor`

Проверяет и сообщает о проблемах конфигурации:

```bash
php artisan azguard:doctor
```

Что проверяет:
- Осиротевшие записи user → role (роль удалена из кода)
- Несоответствие namespace панелей
- Истёкшие прямые гранты
- Enum-кейсы, не реализующие `PermissionInterface`

## `azguard:cache-clear`

```bash
php artisan azguard:cache-clear

# Сброс кэша конкретного пользователя
php artisan azguard:cache-clear --user=42
```

## `azguard:purge-expired-grants`

```bash
php artisan azguard:purge-expired-grants
```

Удаляет истёкшие прямые гранты из таблицы. Добавьте в расписание:

```php
$schedule->command('azguard:purge-expired-grants')->hourly();
```

## `azguard:list-roles`

```bash
php artisan azguard:list-roles
```

Выводит таблицу всех зарегистрированных ролей с количеством пользователей и списком прав.
