# Разрешения

В AzGuard права — это PHP **enum-кейсы**, а не строки в базе данных. Это даёт IDE-автодополнение, статический анализ и diff-код review в Git.

## Определение прав

```php
// app/AzGuard/App/Permissions/PostsPermission.php
namespace App\AzGuard\App\Permissions;

use AzGuard\Contracts\PermissionInterface;
use AzGuard\Attributes\GateAbility;

enum PostsPermission: string implements PermissionInterface
{
    #[GateAbility]                  // регистрирует 'app.posts.view' в Gate
    case View   = 'posts.view';

    case Create = 'posts.create';
    case Edit   = 'posts.edit';
    case Delete = 'posts.delete';
}
```

### Как формируется полный ключ разрешения

`{panel}.{enum-value}` — например, `PostsPermission::View` = `'posts.view'` в панели `app` → `app.posts.view`.

## Проверка прав

```php
$user->hasPermission(PostsPermission::View);   // enum-кейс
$user->hasPermission('app.posts.view');         // полный ключ

// Проверка нескольких сразу
$user->hasAllPermissions([
    PostsPermission::View,
    PostsPermission::Edit,
]);   // true если есть все

$user->hasAnyPermission([
    PostsPermission::Create,
    PostsPermission::Edit,
]);   // true если есть хотя бы одно
```

## Атрибут `#[GateAbility]`

```php
enum PostsPermission: string implements PermissionInterface
{
    // Ключ Gate: 'app.posts.view'
    #[GateAbility]
    case View = 'posts.view';

    // Кастомный ключ Gate
    #[GateAbility(ability: 'view-published-posts')]
    case ViewPublished = 'posts.view-published';

    // Нет атрибута — не регистрируется в Gate,
    // но доступно через hasPermission()
    case InternalFlag = 'posts.internal-flag';
}
```

## Панельные пространства имён

```php
// app/AzGuard/Admin/Permissions/UsersPermission.php
enum UsersPermission: string implements PermissionInterface
{
    #[GateAbility]  // 'admin.users.view'
    case View   = 'users.view';
    case Edit   = 'users.edit';
    case Delete = 'users.delete';
}
```

`app.posts.view` и `admin.users.view` — совершенно независимые права.

::: tip
Папка `App\AzGuard\{Panel}\Permissions\` соответствует панели `{panel}`. AzGuard автоматически определяет префикс из пути.
:::

→ [Каталог разрешений](/ru/guide/permission-catalog) · [Панели](/ru/guide/panels)
