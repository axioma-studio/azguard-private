# Несколько Guards

AzGuard поддерживает несколько Laravel Guards — например, `web` для пользователей и `admin` для администраторов.

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

## Переключение контекста

```php
// Проверка в контексте конкретной панели
AzGuard::forPanel('admin')->hasPermission($user, AdminPermission::ManageUsers);

// Или через Guard
Gate::guard('admin')->allows('admin.users.manage');
```

## Middleware по Guard

```php
// routes/admin.php
Route::middleware(['auth:admin', 'azguard:admin.users.view'])
    ->group(function () {
        Route::get('/admin/users', [AdminUserController::class, 'index']);
    });
```

## Изоляция пространств имён

Пользователь может иметь роль `editor` в панели `app` и роль `viewer` в панели `admin` — они полностью независимы:

```php
$user->assignRole(EditorRole::class, panel: 'app');
$user->assignRole(ViewerRole::class, panel: 'admin');

$user->hasPermission(PostsPermission::Edit);       // app — true
$user->hasPermission(AdminUsersPermission::Delete); // admin — false
```
