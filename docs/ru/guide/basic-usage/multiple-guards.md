# Несколько Guards

AzGuard поддерживает несколько Laravel-гардов одновременно, каждый со своими панелями AzGuard.

## Конфигурация

```php
// config/az-guard.php
'panels' => [
    App\AzGuard\App\AppPanelProvider::class,
    App\AzGuard\Admin\AdminPanelProvider::class,
    App\AzGuard\Api\ApiPanelProvider::class,
],
```

Каждый provider панели объявляет свои роли и разрешения через `permissionEnums()` и `roleClasses()`.

## Проверка в рамках панели

```php
// Текущая панель запроса (устанавливается middleware azguard.panel)
$user->hasPermission(AdminPermission::ManageUsers);

// Явное указание панели вторым аргументом
$user->hasPermission(AdminPermission::ManageUsers, 'admin');
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
->plugin(AzGuardPlugin::make()->forPanel('admin'))
```

→ [Панели](/ru/guide/advanced/panels) · [HTTP и Middleware](/ru/guide/basic-usage/http-access)
