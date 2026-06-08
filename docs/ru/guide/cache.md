# Кэш

## Конфигурация

```php
// config/azguard.php
'cache' => [
    'enabled' => true,
    'ttl'     => 300,     // секунды (5 минут)
    'store'   => 'redis', // null = default cache store
    'prefix'  => 'azguard',
],
```

## Инвалидация

Кэш автоматически инвалидируется при:
- `assignRole()` / `revokeRole()`
- `grant()` / `revoke()`
- `syncRoles()`

Ручная инвалидация:

```php
use AzGuard\Facades\AzGuard;

// Для конкретного пользователя
AzGuard::clearCache($user);

// Для конкретной панели
AzGuard::clearCache($user, panel: 'admin');

// Полная очистка
AzGuard::clearAllCache();
```

## Инвалидация через Events

```php
// При обновлении роли из UI — сбросьте кэш всех затронутых пользователей
Event::listen(RoleUpdated::class, function (RoleUpdated $event) {
    User::role($event->roleClass)->each(function ($user) {
        AzGuard::clearCache($user);
    });
});
```

## Octane

При использовании Octane кэш в памяти живёт только в рамках одного воркера. Используйте Redis-кэш для консистентности между воркерами:

```php
'cache' => [
    'enabled' => true,
    'store'   => 'redis',
],
```
