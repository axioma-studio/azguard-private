# Прямые гранты

Прямой грант — это разрешение, выданное **конкретному пользователю** без присвоения роли. Опционально ограничено по времени (TTL).

## Выдача гранта

```php
// Бессрочный грант
$user->grantPermission(ReportsPermission::Export);

// С TTL
$user->grantPermission(
    permission: ReportsPermission::Export,
    expiresAt: now()->addHours(24),
);

// С опциональным замечанием
$user->grantPermission(
    permission: ReportsPermission::Export,
    expiresAt: now()->addHour(),
    note: 'Бета-доступ, выдан через админпанель',
);
```

## Отзыв гранта

```php
$user->revokePermission(ReportsPermission::Export);

// Отзвать все гранты
$user->revokeAllDirectPermissions();
```

## Проверка и получение

```php
// Был ли грант выдан?
$user->hasDirectPermission(ReportsPermission::Export);  // true / false

// Список всех действующих грантов
$user->directPermissions();  // Collection
```

## TTL и автоматическая очистка

Истёкшие гранты не влияют на `hasPermission()` автоматически. Для регулярной очистки используйте Artisan-команду:

```bash
php artisan azguard:purge-expired-grants
```

Или запланируйте через планировщик:

```php
// app/Console/Kernel.php
$schedule->command('azguard:purge-expired-grants')->hourly();
```

## Приоритет проверки

`hasPermission()` возвращает `true`, если право предоставляется **хотя бы одним** из:

1. Роль пользователя (RoleInterface::permissions)
2. Действующий прямой грант (expiresAt ещё не наступил)
3. Super-admin (если настроен)

→ [Рецепт: временный доступ](/ru/guide/recipes/temp-access-via-grant)
