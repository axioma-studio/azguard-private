# Быстрый старт

За 5 минут до работающего RBAC.

## 1. Установка

```bash
composer require axioma-studio/azguard
php artisan vendor:publish --tag=azguard-config
php artisan vendor:publish --tag=azguard-migrations
php artisan migrate
```

## 2. Добавьте трейт в User

```php
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable
{
    use HasAzGuard;
}
```

## 3. Создайте enum прав

```php
// app/AzGuard/App/Permissions/PostsPermission.php
namespace App\AzGuard\App\Permissions;

use AzGuard\Contracts\PermissionInterface;
use AzGuard\Attributes\GateAbility;

enum PostsPermission: string implements PermissionInterface
{
    #[GateAbility]
    case View   = 'posts.view';
    case Create = 'posts.create';
    case Edit   = 'posts.edit';
    case Delete = 'posts.delete';
}
```

## 4. Создайте роль

```php
// app/AzGuard/App/Roles/EditorRole.php
namespace App\AzGuard\App\Roles;

use AzGuard\Contracts\RoleInterface;
use App\AzGuard\App\Permissions\PostsPermission;

class EditorRole implements RoleInterface
{
    public function permissions(): array
    {
        return [
            PostsPermission::View,
            PostsPermission::Create,
            PostsPermission::Edit,
        ];
    }
}
```

## 5. Назначьте роль и проверьте

```php
// Назначение
$user->assignRole(EditorRole::class);

// Проверка
$user->hasPermission(PostsPermission::View);  // true
Gate::allows('app.posts.view');               // true

// Blade
@can('app.posts.edit')
    <a href="{{ route('posts.edit', $post) }}">Редактировать</a>
@endcan
```

::: tip
Полный список конфигурации — в разделе [Установка](/ru/guide/installation).
:::
