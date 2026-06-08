# Роли

В AzGuard роль — это PHP-класс, реализующий `RoleInterface`. Она определяет список enum-кейсов — база данных хранит только связку `user → role`.

## Определение роли

```php
// app/AzGuard/App/Roles/EditorRole.php
namespace App\AzGuard\App\Roles;

use AzGuard\Contracts\RoleInterface;
use App\AzGuard\App\Permissions\PostsPermission;
use App\AzGuard\App\Permissions\CommentsPermission;

class EditorRole implements RoleInterface
{
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

## Назначение / снятие

```php
// Назначить одну роль
$user->assignRole(EditorRole::class);

// Назначить несколько
$user->assignRoles([EditorRole::class, ModeratorRole::class]);

// Снять
$user->removeRole(EditorRole::class);

// Синхронизация: только эти роли (остальные снимаются)
$user->syncRoles([EditorRole::class]);
```

## Проверка

```php
$user->hasRole(EditorRole::class);           // true / false
$user->hasAnyRole([EditorRole::class, ...]);
$user->hasAllRoles([...]);

$user->roles();  // Collection со всеми ролями
```

## Наследование ролей

```php
class AdminRole implements RoleInterface
{
    public function permissions(): array
    {
        return [
            ...(new EditorRole)->permissions(),  // все права Editor
            UsersPermission::Manage,
            SettingsPermission::Edit,
        ];
    }
}
```

## Регистрация в БД

```bash
# Синхронизировать PHP-роли с таблицей roles
php artisan azguard:sync-roles
```

::: tip
`sync-roles` идемпотентна. Запускайте в миграциях или CI при деплое.
:::

→ [Лучшие практики: роли vs разрешения](/ru/guide/best-practices)
