# Роли

Роль в AzGuard — это **PHP-класс**, реализующий `RoleInterface`. Он содержит список enum-кейсов разрешений, которые получает пользователь при назначении роли.

## Определение роли

```php
// app/AzGuard/App/Roles/EditorRole.php
namespace App\AzGuard\App\Roles;

use AzGuard\Contracts\RoleInterface;
use App\AzGuard\App\Permissions\PostsPermission;
use App\AzGuard\App\Permissions\CommentsPermission;

class EditorRole implements RoleInterface
{
    public function getName(): string
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

## Назначение и отзыв

```php
// Назначить
$user->assignRole(EditorRole::class);

// Несколько ролей
$user->assignRole(EditorRole::class);
$user->assignRole(ModeratorRole::class);

// Отозвать конкретную
$user->revokeRole(EditorRole::class);

// Отозвать все
$user->revokeAllRoles();
```

## Проверка роли

```php
$user->hasRole('editor');             // по имени
$user->hasRole(EditorRole::class);    // по классу
$user->hasAnyRole(['editor', 'admin']); // любая из списка

// В Blade
@role('editor')
    <span>Вы редактор</span>
@endrole
```

## Роли в БД

Roles хранятся в БД по полному имени класса. При переименовании класса выполните:

```bash
php artisan azguard:sync-roles
```

Эта команда синхронизирует PHP-классы с таблицей `azguard_user_roles`.

## Наследование

```php
class AdminRole implements RoleInterface
{
    public function permissions(): array
    {
        return [
            // Наследуем все права редактора
            ...(new EditorRole())->permissions(),
            // Добавляем эксклюзивные
            PostsPermission::Delete,
            UsersPermission::Manage,
        ];
    }
}
```
