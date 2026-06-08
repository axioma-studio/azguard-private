# Панели

Панели — это изолированные пространства имён AzGuard. Каждое приложение имеет одну или несколько панелей: `app`, `admin`, `api`.

## Объявление панели

```php
// app/AzGuard/App/AppPanel.php
namespace App\AzGuard\App;

use AzGuard\Contracts\PanelInterface;

class AppPanel implements PanelInterface
{
    public function name(): string
    {
        return 'app';
    }

    public function roles(): array
    {
        return [
            Roles\EditorRole::class,
            Roles\ModeratorRole::class,
            Roles\ViewerRole::class,
        ];
    }

    public function permissions(): array
    {
        return [
            Permissions\PostsPermission::class,
            Permissions\CommentsPermission::class,
        ];
    }
}
```

## Регистрация

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

## Изоляция панелей

Пользователь может иметь роль `EditorRole` в панели `app` и роль `ViewerRole` в панели `admin` — они полностью независимы:

```php
$user->assignRole(EditorRole::class, panel: 'app');
$user->assignRole(ViewerRole::class, panel: 'admin');

$user->hasPermission(PostsPermission::Edit);         // true (app)
$user->hasPermission(AdminPermission::ManageUsers);  // false (admin)
```
