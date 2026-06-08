# Производительность

## Кэширование прав пользователя

AzGuard кэширует права пользователя в памяти в рамках одного запроса. Для кэширования между запросами:

```php
// config/azguard.php
'cache' => [
    'enabled' => true,
    'ttl'     => 300, // секунды
    'store'   => 'redis',
],
```

## Избегайте N+1 в циклах

```php
// ❌ N+1 — запрос к БД для каждого пользователя
foreach ($users as $user) {
    if ($user->hasPermission(PostsPermission::Edit)) { ... }
}

// ✅ Загрузите роли заранее
$users = User::with('azguardRoles')->get();
foreach ($users as $user) {
    if ($user->hasPermission(PostsPermission::Edit)) { ... }
}
```

## Gate::allows vs hasPermission

```php
// hasPermission() — напрямую через AzGuard, быстрее
$user->hasPermission(PostsPermission::Edit);

// Gate::allows() — проходит через весь стек Gate (before, policies)
// Используйте когда нужна Policy-логика
Gate::allows('app.posts.edit');
```

## Preload разрешений для Inertia

```php
// Загружайте abilities один раз в HandleInertiaRequests
// не вызывайте hasPermission() отдельно для каждого компонента
public function share(Request $request): array
{
    return [
        'abilities' => fn () => $request->user()?->abilities() ?? [],
    ];
}
```
