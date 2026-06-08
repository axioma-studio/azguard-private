# Обзор

Eта страница — быстрый справочник по всем основным операциям AzGuard. Подробности — в отдельных разделах.

## Проверка прав

```php
// Enum-кейс (рекомендуется — IDE автодополняет)
$user->hasPermission(PostsPermission::Edit);   // true / false

// Строка (полный ключ с панелью)
$user->hasPermission('app.posts.edit');         // то же самое

// Laravel Gate — работает везде, где работает Gate
Gate::allows('app.posts.edit');                 // true / false
$this->authorize('app.posts.edit');             // бросает исключение
```

## Работа с ролями

```php
// Назначить роль
$user->assignRole(EditorRole::class);

// Отозвать
$user->revokeRole(EditorRole::class);

// Проверить
$user->hasRole('editor');  // true / false

// Получить все роли
$user->roles;  // коллекция
```

## Blade

```blade
@can('app.posts.edit')
    <a href="{{ route('posts.edit', $post) }}">Редактировать</a>
@endcan

@role('editor')
    <span class="badge">Редактор</span>
@endrole

@hasanypermission(['app.posts.edit', 'app.posts.delete'])
    <div class="actions">...</div>
@endhasanypermission
```

## Middleware

```php
// routes/web.php
Route::middleware('azguard:app.posts.edit')->group(function () {
    Route::put('/posts/{post}', [PostController::class, 'update']);
});
```

## Атрибут на контроллере

```php
#[CheckPermission(PostsPermission::Edit)]
public function update(Request $request, Post $post): Response
{
    // Доступ автоматически проверен до входа в метод
}
```

→ [Права](/ru/guide/permissions) · [Роли](/ru/guide/roles) · [Прямые гранты](/ru/guide/direct-grants)
