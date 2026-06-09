# Несколько Guards

AzGuard поддерживает несколько Laravel-гардов одновременно, каждый со своими панелями AzGuard.

## Конфигурация

```php
// config/azguard.php
'panels' => [
    'app'   => App\AzGuard\App\AppPanel::class,
    'admin' => App\AzGuard\Admin\AdminPanel::class,
    'api'   => App\AzGuard\Api\ApiPanel::class,
],
```

Каждый Panel-класс импортирует свои роли и разрешения.

## Проверка в рамках guard

```php
// Текущий guard Laravel (определяется роутом)
$user->hasPermission(AdminPermission::ManageUsers);   // panel из namespace

// Явная указка панели
$user->forPanel('admin')->hasPermission(AdminPermission::ManageUsers);
```

## Middleware для разных guard

```php
// routes/admin.php
Route::middleware(['auth:admin', 'azguard.panel:admin'])
    ->group(function () {
        Route::get('/users', [UserController::class, 'index']);
    });

// routes/api.php
Route::middleware(['auth:api', 'azguard.panel:api'])
    ->group(function () {
        Route::get('/me/permissions', [ProfileController::class, 'permissions']);
    });
```

## Filament с несколькими панелями

```php
// app/Providers/Filament/AdminPanelProvider.php
->authGuard('admin')
->plugin(AzGuardFilamentPlugin::make()->panel('admin'))
```

→ [Панели](/ru/guide/panels) · [HTTP и Middleware](/ru/guide/http-access)
