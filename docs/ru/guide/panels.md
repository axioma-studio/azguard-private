# Панели

Панель — это изолированное пространство имён для разрешений и ролей. Типичное приложение имеет три панели: `app`, `admin`, `api`.

## Provider панели

Панель описывается классом-провайдером, наследующим `AzGuard\PanelProvider`.
Метод `panel()` собирает панель через fluent-API.

```php
// app/AzGuard/App/AppPanelProvider.php
namespace App\AzGuard\App;

use AzGuard\PanelProvider;
use AzGuard\Support\Panel;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app') // префикс для всех прав: app.posts.view
            ->permissionEnums([
                Permissions\PostsPermission::class,
                Permissions\CommentsPermission::class,
                Permissions\ReportsPermission::class,
            ])
            ->roleClasses([
                Roles\EditorRole::class,
                Roles\ViewerRole::class,
                Roles\ModeratorRole::class,
            ]);
    }
}
```

## Регистрация в конфиге

В `panels` перечисляются FQCN провайдеров панелей:

```php
// config/az-guard.php
'panels' => [
    App\AzGuard\App\AppPanelProvider::class,
    App\AzGuard\Admin\AdminPanelProvider::class,
    App\AzGuard\Api\ApiPanelProvider::class,
],
```

## Изоляция прав

Права между панелями не пересекаются:

```php
// app.users.view и admin.users.view — разные права
$user->hasPermission('app.users.view');   // false — нет роли в app
$user->hasPermission('admin.users.view'); // true  — есть роль в admin
```
