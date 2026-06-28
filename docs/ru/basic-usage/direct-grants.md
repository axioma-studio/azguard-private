# Прямые гранты

Прямой грант — это разрешение, выданное **конкретному пользователю** без присвоения роли. Опционально ограничено по времени (TTL).

## Выдача гранта

```php
// Бессрочный грант (panelId обязателен)
$user->grant(ReportsPermission::Export, 'app');

// С TTL — передайте дату истечения
$user->grant(ReportsPermission::Export, 'app', now()->addHours(24));

// Fluent-билдер: ttl() задаётся в секундах
AzGuard::forUser($user)->on('app')->ttl(86400)->grant(ReportsPermission::Export);

// Короткая форма через фасад: AzGuard::grant($user, $perm, $panelId = 'app', ?int $ttl)
AzGuard::grant($user, ReportsPermission::Export, 'app', 3600);
```

## Отзыв гранта

```php
$user->revoke(ReportsPermission::Export, 'app');

// Отозвать все гранты пользователя в панели
AzGuard::forUser($user)->on('app')->revokeAll();
```

## Проверка и получение

```php
// Был ли грант выдан?
$user->hasGrant(ReportsPermission::Export, 'app');  // true / false

// Список всех действующих грантов в панели
$user->grants('app');  // Collection
```

## TTL и автоматическая очистка

Истёкшие гранты автоматически игнорируются при проверке `hasPermission()` —
их отфильтровывает scope `active()`. Команда нужна лишь чтобы держать таблицу чистой:

```bash
php artisan guard:prune-grants
```

Можно ограничить конкретной панелью:

```bash
php artisan guard:prune-grants --panel=app
```

Либо включить ежедневный запуск через `prune_expired_daily => true` в `config/az-guard.php`.

## Приоритет проверки

`hasPermission()` возвращает `true`, если право предоставляется **хотя бы одним** из:

1. Роль пользователя (RoleInterface::permissions)
2. Действующий прямой грант (expiresAt ещё не наступил)
3. Super-admin (если настроен)

→ [Рецепт: временный доступ](/ru/recipes/temp-access-via-grant)
