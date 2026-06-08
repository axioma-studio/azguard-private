# Прямые гранты

Прямой грант — это право доступа, выданное конкретному пользователю **без привязки к роли**, с опциональным TTL (временем истечения).

## Когда использовать

- Временный доступ к бета-функции
- Разовый экспорт данных
- Переопределение роли для одного пользователя
- Доступ во время отпуска коллеги

## Выдача гранта

```php
use AzGuard\Facades\AzGuard;

// Бессрочный грант
AzGuard::grant($user, PostsPermission::Delete);

// Грант на 1 час
AzGuard::grant($user, PostsPermission::Delete, ttl: 3600);

// Грант до конкретной даты
AzGuard::grant($user, PostsPermission::Delete, expiresAt: now()->addDays(7));

// Грант с контекстом (только для этого проекта)
AzGuard::grant($user, PostsPermission::Edit, context: ['project_id' => 42]);
```

## Отзыв гранта

```php
// Отозвать конкретное право
AzGuard::revoke($user, PostsPermission::Delete);

// Отозвать все прямые гранты пользователя
AzGuard::revokeAll($user);
```

## Проверка

```php
// hasPermission() учитывает прямые гранты автоматически
$user->hasPermission(PostsPermission::Delete); // true, если есть активный грант

// Проверить только прямые гранты
AzGuard::hasDirectGrant($user, PostsPermission::Delete); // bool

// Посмотреть все активные гранты
$user->directGrants(); // Collection<DirectGrant>
```

## Автоматическое истечение

Просроченные гранты фильтруются при каждой проверке. Для очистки БД запустите:

```bash
php artisan azguard:prune-grants
```

Или добавьте в планировщик:

```php
// bootstrap/app.php
->withSchedule(function (Schedule $schedule) {
    $schedule->command('azguard:prune-grants')->daily();
})
```
