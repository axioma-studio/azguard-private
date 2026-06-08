# Разрешения

В AzGuard разрешения — это **PHP enum-кейсы**, реализующие `PermissionInterface`. Никаких magic-строк.

## Объявление разрешений

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

### Полный ключ разрешения

Ключ в Gate формируется как `{panel}.{enum_value}`:
- Панель `app` + `posts.view` → `app.posts.view`
- Панель `admin` + `posts.view` → `admin.posts.view`

## Атрибут GateAbility

Только кейсы с `#[GateAbility]` автоматически регистрируются в `Gate::before()`. Кейсы без атрибута доступны только через `hasPermission()`.

```php
enum ReportsPermission: string implements PermissionInterface
{
    #[GateAbility]
    case View   = 'reports.view';    // доступно через Gate::allows()
    case Export = 'reports.export';  // только $user->hasPermission()
}
```

## Группировка по домену

Рекомендуемая структура:

```
app/AzGuard/
├── App/
│   ├── Permissions/
│   │   ├── PostsPermission.php
│   │   ├── CommentsPermission.php
│   │   └── UsersPermission.php
│   └── Roles/
├── Admin/
│   ├── Permissions/
│   └── Roles/
└── Api/
    ├── Permissions/
    └── Roles/
```

## Проверка разрешений

```php
// Enum-кейс (рекомендуется)
$user->hasPermission(PostsPermission::Edit);  // ✅ type-safe

// Строка (для обратной совместимости)
$user->hasPermission('app.posts.edit');

// Laravel Gate
Gate::allows('app.posts.edit');

// Blade
@can('app.posts.edit') ... @endcan

// В тестах
$this->actingAs($user)->assertTrue(
    $user->hasPermission(PostsPermission::Edit)
);
```

## Статический анализ

Поскольку разрешения — это enum-кейсы, PHPStan и Psalm находят опечатки на этапе проверки типов:

```php
// PHPStan ошибка: PostsPermission::Edut не существует
$user->hasPermission(PostsPermission::Edut);

// ✅ OK
$user->hasPermission(PostsPermission::Edit);
```
