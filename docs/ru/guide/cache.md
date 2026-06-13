# Кеш

AzGuard использует двухуровневое кеширование: **in-memory** (на время запроса) и опциональный **persistent cache** (Redis/Memcached).

## Конфигурация

```php
// config/azguard.php
'cache' => [
    'enabled' => true,
    'store'   => env('AZGUARD_CACHE_STORE', 'redis'),
    'ttl'     => env('AZGUARD_CACHE_TTL', 300), // секунд
    'prefix'  => 'azguard_',
],
```

## Сброс кеша

```php
// Сброс для конкретного пользователя
AzGuard::clearCache($user);

// Сброс всего кеша
AzGuard::clearAllCache();

// Через Artisan
php artisan azguard:clear-cache
php artisan azguard:clear-cache --user=42
```

## Автоматический сброс

Кеш автоматически сбрасывается при:
- `assignRole()` / `revokeRole()`
- `grantPermission()` / `revokeedPermission()`

## Кеш в Octane

In-memory кеш AzGuard **не** переживает между запросами в Octane — это ожидаемое поведение. Persistent cache (Redis) сохраняется.

```php
// Для Octane: используйте короткий TTL
'cache' => [
    'store' => 'redis',
    'ttl'   => 60, // 1 минута достаточно при Octane
],
```
