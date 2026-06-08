# Роли

В AzGuard роль — это **PHP-класс**, реализующий `RoleInterface`. Список разрешений определяется в коде.

## Объявление роли

```php
// app/AzGuard/App/Roles/EditorRole.php
namespace App\AzGuard\App\Roles;

use AzGuard\Contracts\RoleInterface;
use App\AzGuard\App\Permissions\PostsPermission;
use App\AzGuard\App\Permissions\CommentsPermission;

class EditorRole implements RoleInterface
{
    public function name(): string
    {
        return 'editor';
    }

    public function permissions(): array
    {
        return [
            PostsPermission::View,
            PostsPermission::Create,
            PostsPermission::Edit,
            CommentsPermission::View,
            CommentsPermission::Moderate,
        ];
    }
}
```

## Работа с ролями

```php
// Назначить роль
$user->assignRole(EditorRole::class);

// Назначить несколько
$user->assignRoles([EditorRole::class, ModeratorRole::class]);

// Синхронизировать (удаляет старые, назначает новые)
$user->syncRoles([EditorRole::class]);

// Отозвать
$user->revokeRole(EditorRole::class);
$user->revokeAllRoles();

// Проверить
$user->hasRole(EditorRole::class);    // bool
$user->hasAnyRole([EditorRole::class, AdminRole::class]); // bool
$user->hasAllRoles([EditorRole::class, ModeratorRole::class]); // bool

// Получить все роли
$user->roles(); // Collection
```

## Роли как значение в БД

В таблице `azguard_user_roles` хранится FQCN класса:

```
user_id | role_class                              | panel
1       | App\AzGuard\App\Roles\EditorRole        | app
1       | App\AzGuard\Admin\Roles\ModeratorRole   | admin
```

## Синхронизация через artisan

```bash
# Синхронизировать классы ролей с записями в БД
php artisan azguard:sync-roles

# Диагностика — найти устаревшие записи
php artisan azguard:doctor
```

## Иерархия ролей (наследование)

```php
class SeniorEditorRole implements RoleInterface
{
    public function permissions(): array
    {
        return [
            // Включаем все права EditorRole
            ...(new EditorRole())->permissions(),
            // Добавляем дополнительные
            PostsPermission::Delete,
            PostsPermission::Publish,
        ];
    }
}
```
