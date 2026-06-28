# Кеш

AzGuard кеширует эффективный набор прав пользователя. По умолчанию используется in-memory стор `array` (на время запроса); для кеширования между запросами выберите persistent-стор (Redis/Memcached).

## Конфигурация

```php
// config/az-guard.php
'cache' => [
    'store'           => 'array',                 // 'array' | 'redis' | 'memcached' | 'file' | 'default'
    'expiration_time' => 3600,                     // TTL в секундах; null = без истечения
    'key'             => 'azguard.permissions',    // префикс ключа
],
```

`store => 'array'` отключает кросс-реквест кеш (только in-memory) — удобно для тестов.

## Сброс кеша

```bash
# Сброс всего кеша прав
php artisan guard:cache-reset

# Без подтверждения
php artisan guard:cache-reset --force
```

## Автоматический сброс

Кеш сбрасывается автоматически при изменении ролей и грантов — отдельный слушатель регистрировать не нужно. Это происходит при:

- `assignRole()` / `removeRole()` / `syncRoles()`
- `grant()` / `revoke()`

а также по соответствующим событиям `RoleAttached`, `RoleDetached`, `GrantGiven`, `GrantRevoked`.

## Кеш в Octane

AzGuard биндит свои per-request сервисы как `scoped` и сбрасывает их между запросами — он Octane-safe. In-memory часть кеша не переживает между запросами; persistent-стор (Redis) сохраняется.

```php
// Для Octane: persistent-стор с коротким TTL
'cache' => [
    'store'           => 'redis',
    'expiration_time' => 60, // 1 минута достаточно при Octane
    'key'             => 'azguard.permissions',
],
```
