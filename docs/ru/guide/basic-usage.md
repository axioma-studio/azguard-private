# Обзор: Основы

Этот раздел показывает ключевые примитивы AzGuard: проверка прав, назначение ролей, прямые гранты и интеграция с Laravel Gate.

## Проверка прав

```php
// Проверка через модель
$user->hasPermission(PostsPermission::View);        // true / false
$user->hasPermission('app.posts.view');              // то же

// Проверка роли
$user->hasRole(EditorRole::class);                   // true / false

// Laravel Gate
Gate::allows('app.posts.view');                      // true / false
$this->authorize('update', $post);                   // 403 если запрещено

// Blade
@can('app.posts.edit') ... @endcan
@role(EditorRole::class) ... @endrole
```

## Назначение / снятие роли

```php
// Назначить
$user->assignRole(EditorRole::class);
$user->assignRoles([EditorRole::class, ModeratorRole::class]);

// Снять
$user->removeRole(EditorRole::class);

// Все роли пользователя
$user->roles();   // Collection
```

## PHP 8 Attributes

Декларативная защита методов контроллера:

```php
// Проверка перед выполнением метода
#[CheckPermission(PostsPermission::View)]
public function index(): Response { ... }

// Проверка с route model binding
#[CheckPermission(permission: PostsPermission::Edit, arguments: ['post'])]
public function edit(Post $post): Response { ... }

// Пропустить проверку (super-admin, внутренние CLI-команды)
#[SkipGuardCheck]
public function internalSync(): void { ... }
```

## Прямые гранты

```php
// Выдать одно право на 1 час
$user->grantPermission(
    permission: ReportsPermission::Export,
    expiresAt: now()->addHour(),
);

// Отозвать
$user->revokePermission(ReportsPermission::Export);
```

→ [Подробнее о прямых грантах](/ru/guide/direct-grants)

## Query scopes

```php
// Все пользователи с ролью Editor
User::role(EditorRole::class)->get();

// Все пользователи без какой-либо роли
User::withoutRole()->get();
```

→ [Разрешения](/ru/guide/permissions) · [Роли](/ru/guide/roles) · [Blade-директивы](/ru/guide/blade-directives)
