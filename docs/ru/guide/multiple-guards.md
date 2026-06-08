# Несколько Guards

AzGuard поддерживает несколько auth guards — каждый панель может работать со своим guard.

## Конфигурация

```php
// config/azguard.php
return [
    'panels' => [
        'app'   => App\AzGuard\App\AppPanel::class,
        'admin' => App\AzGuard\Admin\AdminPanel::class,
        'api'   => App\AzGuard\Api\ApiPanel::class,
    ],
];
```

```php
// config/auth.php
'guards' => [
    'web'   => ['driver' => 'session', 'provider' => 'users'],
    'admin' => ['driver' => 'session', 'provider' => 'admins'],
    'api'   => ['driver' => 'sanctum',  'provider' => 'users'],
],
```

## Проверка в контексте guard

```php
// Явное указание guard
$user->forGuard('admin')->hasPermission(AdminPermission::ManageUsers);

// В middleware
Route::middleware(['auth:admin', 'azguard:admin'])->group(function () {
    Route::get('/admin/users', AdminUsersController::class);
});
```

## Изоляция пространств имён

`app.posts.edit` и `admin.posts.edit` — разные права, даже если enum-значение одинаковое. Пользователь с ролью `app/EditorRole` не получит доступ к admin-маршрутам автоматически.
