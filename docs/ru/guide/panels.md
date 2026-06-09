# Панели

Панель — это изолированное пространство имён для разрешений и ролей. Типичное приложение имеет три панели: `app`, `admin`, `api`.

## Класс панели

```php
// app/AzGuard/App/AppPanel.php
namespace App\AzGuard\App;

use AzGuard\Contracts\PanelInterface;

class AppPanel implements PanelInterface
{
    public function getName(): string
    {
        return 'app'; // префикс для всех прав: app.posts.view
    }

    public function getPermissions(): array
    {
        return [
            Permissions\PostsPermission::class,
            Permissions\CommentsPermission::class,
            Permissions\ReportsPermission::class,
        ];
    }

    public function getRoles(): array
    {
        return [
            Roles\EditorRole::class,
            Roles\ViewerRole::class,
            Roles\ModeratorRole::class,
        ];
    }
}
```

## Регистрация в конфиге

```php
// config/azguard.php
'panels' => [
    'app'   => App\AzGuard\App\AppPanel::class,
    'admin' => App\AzGuard\Admin\AdminPanel::class,
    'api'   => App\AzGuard\Api\ApiPanel::class,
],
```

## Изоляция прав

Права между панелями не пересекаются:

```php
// app.users.view и admin.users.view — разные права
$user->hasPermission('app.users.view');   // false — нет роли в app
$user->hasPermission('admin.users.view'); // true  — есть роль в admin
```
