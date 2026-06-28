# Artisan-команды

AzGuard предоставляет несколько Artisan-команд для синхронизации, диагностики и обслуживания.

## `guard:sync-roles`

Синхронизирует PHP-классы ролей с таблицей `roles` в БД.

```bash
php artisan guard:sync-roles

# С выводом деталей
php artisan guard:sync-roles --verbose
```

Запускайте при деплое или в миграциях.

## `guard:doctor`

Проверяет и сообщает о проблемах конфигурации:

```bash
php artisan guard:doctor
```

Что проверяет:
- Осиротевшие записи user → role (роль удалена из кода)
- Несоответствие namespace панелей
- Истёкшие прямые гранты
- Enum-кейсы, не зарегистрированные на панели

## `guard:cache-reset`

```bash
php artisan guard:cache-reset

# Без запроса подтверждения
php artisan guard:cache-reset --force
```

## `guard:prune-grants`

```bash
php artisan guard:prune-grants
```

Удаляет истёкшие прямые гранты из таблицы. Добавьте в расписание:

```php
$schedule->command('guard:prune-grants')->hourly();
```

## `guard:list-permissions`

```bash
php artisan guard:list-permissions
```

Выводит таблицу всех зарегистрированных прав по панелям.
