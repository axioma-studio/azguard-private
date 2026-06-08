# Обзор основ

Этот раздел охватывает ежедневную работу с AzGuard: проверка прав, назначение ролей, интеграция с Laravel Gate.

## Проверка прав

```php
// Через enum-кейс (рекомендуется — IDE-совместимо)
$user->hasPermission(PostsPermission::Edit);   // true / false

// Через строковый ключ
$user->hasPermission('app.posts.edit');         // то же самое

// Laravel Gate
Gate::allows('app.posts.edit');                 // ✅
$this->authorize('update', $post);              // ✅ через Policy

// Blade
@can('app.posts.edit')
    <a href="...">Редактировать</a>
@endcan
```

## Назначение и отзыв ролей

```php
// Назначить роль
$user->assignRole(EditorRole::class);

// Назначить несколько ролей
$user->assignRoles([EditorRole::class, ModeratorRole::class]);

// Отозвать роль
$user->revokeRole(EditorRole::class);

// Проверить наличие роли
$user->hasRole(EditorRole::class);  // true / false

// Все роли пользователя
$user->roles(); // Collection<RoleInterface>
```

## Проверка через атрибут PHP 8

```php
use AzGuard\Attributes\CheckPermission;

class PostController extends Controller
{
    #[CheckPermission(PostsPermission::View)]
    public function index(): Response
    {
        return Inertia::render('Posts/Index');
    }

    #[CheckPermission(permission: PostsPermission::Edit, arguments: ['post'])]
    public function update(UpdatePostRequest $request, Post $post): Response
    {
        $post->update($request->validated());
        return back();
    }
}
```

::: tip
Атрибут `#[CheckPermission]` виден в инспекции маршрутов и работает через `Gate::authorize()` под капотом.
:::

## Query Scopes

```php
// Пользователи с определённой ролью
User::role(EditorRole::class)->where('active', true)->get();

// Пользователи с определённым правом
User::permission(PostsPermission::Delete)->get();
```
