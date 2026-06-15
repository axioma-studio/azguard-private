# Обзор: Основы

Этот раздел показывает ключевые примитивы AzGuard: проверка прав, назначение ролей, прямые гранты и интеграция с Laravel Gate.

## Проверка прав

```php
// Проверка через модель
$user->hasPermission(PostsPermission::View);        // true / false
$user->hasPermission('app.posts.view');              // то же

// Проверка роли (по имени роли)
$user->hasRole('editor');                            // true / false

// Laravel Gate
Gate::allows('app.posts.view');                      // true / false
$this->authorize('update', $post);                   // 403 если запрещено

// Blade
@can('app.posts.edit') ... @endcan
@azrole('editor') ... @endazrole
```

## Назначение / снятие роли

```php
// Назначить (по имени роли)
$user->assignRole('editor');
$user->assignRole('editor', 'moderator');

// Снять
$user->removeRole('editor');

// Все роли пользователя
$user->roles;   // Collection (отношение)
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
// Выдать одно право на 1 час (ttl в секундах)
AzGuard::forUser($user)->on('app')->ttl(3600)->grant(ReportsPermission::Export);

// Или напрямую через модель (panelId обязателен)
$user->grant(ReportsPermission::Export, 'app', now()->addHour());

// Отозвать
$user->revoke(ReportsPermission::Export, 'app');
```

→ [Подробнее о прямых грантах](/ru/guide/direct-grants)

## Запросы по ролям

Роли — это обычное отношение `roles()`, поэтому фильтруйте через `whereHas` / `whereDoesntHave`:

```php
// Все пользователи с ролью editor
User::whereHas('roles', fn ($q) => $q->where('name', 'editor'))->get();

// Все пользователи без какой-либо роли
User::whereDoesntHave('roles')->get();
```

→ [Разрешения](/ru/guide/permissions) · [Роли](/ru/guide/roles) · [Blade-директивы](/ru/guide/blade-directives)
