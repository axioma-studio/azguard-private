# Производительность

## Кеширование

AzGuard кеширует права пользователя в памяти на время запроса. Запросов к БД на каждую проверку нет.

Для кеширования между запросами настройте Redis-кеш:

```php
// config/azguard.php
'cache' => [
    'enabled' => true,
    'store'   => 'redis',
    'ttl'     => 300, // секунд
],
```

## Избегайте N+1 в циклах

```php
// ❌ Плохо — отдельный запрос для каждого пользователя
foreach ($users as $user) {
    if ($user->hasPermission(PostsPermission::Edit)) { ... }
}

// ✅ Хорошо — eager load ролей
$users = User::with('azguardRoles')->get();
foreach ($users as $user) {
    if ($user->hasPermission(PostsPermission::Edit)) { ... }
}
```

## hasPermission vs Gate::allows

| | `hasPermission()` | `Gate::allows()` |
|---|---|---|
| Прямой вызов | ✅ | ✅ |
| Проходит `Gate::before()` | ❌ | ✅ |
| Учитывает super-admin | ❌ | ✅ |
| Быстрее | немного | — |

Используйте `Gate::allows()` как основной метод — он учитывает super-admin и `Gate::before()` хуки.

## Octane

AzGuard stateless — нет глобального состояния между запросами. Работает с Octane без дополнительной настройки.
