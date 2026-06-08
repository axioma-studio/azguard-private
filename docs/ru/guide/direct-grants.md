# Прямые гранты

Прямой грант — это разрешение, выданное **конкретному пользователю** напрямую, без роли. Опционально с TTL (временем жизни).

## Когда использовать

- Временный доступ к бета-функции
- Экстренный доступ на N часов
- Переопределение для одного пользователя без изменения роли

## Выдача гранта

```php
use Carbon\Carbon;

// Бессрочный грант
$user->grantPermission(ReportsPermission::Export);

// С TTL — истекает через 24 часа
$user->grantPermission(
    ReportsPermission::Export,
    expiresAt: Carbon::now()->addHours(24)
);

// С точной датой
$user->grantPermission(
    ReportsPermission::Export,
    expiresAt: Carbon::parse('2026-12-31 23:59:59')
);
```

## Отзыв гранта

```php
// Отозвать конкретный грант
$user->revokeGrantedPermission(ReportsPermission::Export);

// Отозвать все гранты пользователя
$user->revokeAllGrantedPermissions();
```

## Проверка

Прямые гранты проверяются автоматически через `hasPermission()` и Gate — никакого специального кода не нужно:

```php
$user->hasPermission(ReportsPermission::Export); // учитывает и роли, и гранты
Gate::allows('app.reports.export');              // то же самое
```

## Истёкшие гранты

Истёкшие гранты не удаляются автоматически. Запускайте очистку:

```bash
php artisan azguard:prune-grants
```

Либо в планировщике:

```php
// app/Console/Kernel.php
$schedule->command('azguard:prune-grants')->daily();
```

## Таблица `azguard_direct_grants`

| Колонка | Тип | Описание |
|---|---|---|
| `user_id` | bigint | ID пользователя |
| `permission` | string | Полный ключ разрешения |
| `expires_at` | timestamp\|null | Время истечения (null = бессрочно) |
