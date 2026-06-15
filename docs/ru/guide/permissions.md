# Разрешения

В AzGuard права — это PHP **enum-кейсы**, а не строки в базе данных. Это даёт IDE-автодополнение, статический анализ и diff-код review в Git.

## Определение прав

```php
// app/AzGuard/App/Permissions/PostsPermission.php
namespace App\AzGuard\App\Permissions;

enum PostsPermission: string
{
    case View   = 'posts.view';
    case Create = 'posts.create';
    case Edit   = 'posts.edit';
    case Delete = 'posts.delete';
}
```

Значения enum не содержат префикса панели — его добавляет панель, на которой
зарегистрирован enum (`Panel::permissionEnums([PostsPermission::class])`).

### Как формируется полный ключ разрешения

`{panel}.{enum-value}` — например, `PostsPermission::View` = `'posts.view'` в панели `app` → `app.posts.view`.

## Проверка прав

```php
$user->hasPermission(PostsPermission::View);   // enum-кейс (привязан к панели)
$user->hasPermission('app.posts.view');         // полный строковый ключ

// checkPermission() — то же, но никогда не бросает исключений (безопасно в Blade)
$user->checkPermission(PostsPermission::Edit);

// Несколько прав — обычными PHP-средствами
$canViewAndEdit =
    $user->hasPermission(PostsPermission::View)
    && $user->hasPermission(PostsPermission::Edit);   // все

$canCreateOrEdit =
    $user->hasPermission(PostsPermission::Create)
    || $user->hasPermission(PostsPermission::Edit);   // хотя бы одно
```

## Интеграция с Laravel Gate

AzGuard регистрируется через `Gate::before`, поэтому любой полный ключ права
сразу работает в Gate без отдельной регистрации:

```php
Gate::allows('app.posts.view');     // true / false
$user->can('app.posts.view');        // то же
```

## Атрибут `#[GateAbility]`

`#[GateAbility]` — атрибут уровня **метода**: ставится на методы политики и
связывает их с кейсом enum (используется командами каталога и `guard:doctor`,
а также генератором `make:guard-policy`).

```php
use AzGuard\Attributes\GateAbility;
use App\AzGuard\App\Permissions\PostsPermission;

final class PostPolicy
{
    #[GateAbility(permission: PostsPermission::View)]
    public function canView(User $user): bool
    {
        return $user->hasPermission(PostsPermission::View);
    }
}
```

## Панельные пространства имён

```php
// app/AzGuard/Admin/Permissions/UsersPermission.php
enum UsersPermission: string
{
    case View   = 'users.view';   // в панели admin → 'admin.users.view'
    case Edit   = 'users.edit';
    case Delete = 'users.delete';
}
```

`app.posts.view` и `admin.users.view` — совершенно независимые права.

::: tip
Один и тот же enum регистрируется на конкретной панели через
`Panel::permissionEnums([UsersPermission::class])` — именно панель задаёт префикс ключа.
:::

→ [Каталог разрешений](/ru/guide/permission-catalog) · [Панели](/ru/guide/panels)
