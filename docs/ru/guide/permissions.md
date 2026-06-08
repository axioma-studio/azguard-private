# Разрешения

В AzGuard разрешение — это **PHP enum-кейс**, реализующий `PermissionInterface`. Никаких строк, никакого риска опечаток.

## Определение enum разрешений

```php
// app/AzGuard/App/Permissions/PostsPermission.php
namespace App\AzGuard\App\Permissions;

use AzGuard\Contracts\PermissionInterface;
use AzGuard\Attributes\GateAbility;

enum PostsPermission: string implements PermissionInterface
{
    #[GateAbility]  // регистрирует 'app.posts.view' в Laravel Gate
    case View   = 'posts.view';
    case Create = 'posts.create';
    case Edit   = 'posts.edit';
    case Delete = 'posts.delete';
}
```

Атрибут `#[GateAbility]` регистрирует кейс в Laravel Gate с полным ключом `{panel}.{value}`. Кейсы **без** атрибута доступны только через `hasPermission()`, но не через Gate.

## Структура ключа

```
app.posts.view
│   │     │
│   │     └─ значение enum-кейса
│   └─────── namespace из enum (имя файла/папки)
└─────────── название панели
```

## Проверка прав

```php
// Через трейт — предпочтительный способ
$user->hasPermission(PostsPermission::View);

// Через Gate
Gate::allows('app.posts.view');

// В политиках
public function update(User $user, Post $post): bool
{
    return $user->hasPermission(PostsPermission::Edit);
}

// В Blade
@can('app.posts.edit') ... @endcan
```

## Несколько панелей

Каждая панель имеет собственное пространство имён:

```php
// admin-панель
enum AdminUsersPermission: string implements PermissionInterface
{
    case View   = 'users.view';    // → admin.users.view
    case Delete = 'users.delete';  // → admin.users.delete
}

// app-панель
enum AppUsersPermission: string implements PermissionInterface
{
    case ViewOwn = 'users.view-own';  // → app.users.view-own
}
```

`admin.users.view` и `app.users.view-own` — полностью независимые разрешения.

## Статический анализ

Поскольку права — это enum-кейсы, PHPStan и IDE понимают их:

```php
// ✅ IDE автодополняет, PHPStan проверяет типы
$user->hasPermission(PostsPermission::Edit);

// ❌ Строка — нет автодополнения, нет проверки
$user->hasPermission('app.posts.edi');  // опечатка незаметна до рантайма
```
