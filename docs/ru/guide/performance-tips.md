# Производительность

## Кеширование

AzGuard кеширует права пользователя в памяти на время запроса. Запросов к БД на каждую проверку нет.

Для кеширования между запросами настройте Redis-кеш:

```php
// config/az-guard.php
'cache' => [
    'store'           => 'redis',
    'expiration_time' => 300, // секунд
    'key'             => 'azguard.permissions',
],
```

## Избегайте N+1 в циклах

```php
// ❌ Плохо — отдельный запрос для каждого пользователя
foreach ($users as $user) {
    if ($user->hasPermission(PostsPermission::Edit)) { ... }
}

// ✅ Хорошо — eager load ролей
$users = User::with('roles')->get();
foreach ($users as $user) {
    if ($user->hasPermission(PostsPermission::Edit)) { ... }
}
```

## hasPermission vs Gate::allows

| | `hasPermission()` | `Gate::allows()` |
|---|---|---|
| Прямой вызов | ✅ | ✅ |
| Проходит `Gate::before()` | ❌ | ✅ |
| Wildcard-роль (`['*']`) | ✅ | ✅ |
| Быстрее | немного | — |

`hasPermission()` учитывает wildcard-роль super-admin (через grant source), но не вызывает ваши `Gate::before()`-замыкания. Используйте `Gate::allows()` как основной метод, если опираетесь на `Gate::before()`-хуки.

## Octane

AzGuard stateless — нет глобального состояния между запросами. Работает с Octane без дополнительной настройки.
