# Политики и Gate

AzGuard полностью совместим с системой политик и Gate Laravel.

## Gate

```php
// Регистрация через Gate::define (если нужна кастомная логика)
Gate::define('update-post', function (User $user, Post $post) {
    return $user->hasPermission(PostsPermission::Edit) && $post->user_id === $user->id;
});

// Проверка
Gate::allows('update-post', $post);  // true / false
$this->authorize('update-post', $post);  // бросает исключение
```

## Политики

```php
// app/Policies/PostPolicy.php
class PostPolicy
{
    public function view(User $user, Post $post): bool
    {
        return $user->hasPermission(PostsPermission::View);
    }

    public function update(User $user, Post $post): bool
    {
        // Право редактирования + владелец или публикатор
        return $user->hasPermission(PostsPermission::Edit)
            && ($post->user_id === $user->id || $user->hasRole(PublisherRole::class)); // 'publisher' по имени тоже работает
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->hasPermission(PostsPermission::Delete);
    }
}
```

```php
// В контроллере
$this->authorize('update', $post);   // вызовет PostPolicy::update()

// В Blade
@can('update', $post)
    <button>Редактировать</button>
@endcan
```

## Регистрация политик

```php
// app/Providers/AuthServiceProvider.php
protected $policies = [
    Post::class => PostPolicy::class,
];
```

## Enum против строки в нативном Gate

Директива `@azcan` и `$user->hasPermission()` понимают enum: передавайте кейс
напрямую — он привязывается к панели автоматически. Нативный Laravel Gate
(`Gate::allows()`, `Gate::authorize()`, `@can`, `$user->can()`) — наоборот:
ему нужен **полный строковый ключ с префиксом панели** (`app.posts.view`).
«Голый» enum сюда передавать нельзя — Laravel приведёт его к `->value` без
префикса панели, и совпадения с панельным ключом не будет. Выводите ключ из
enum через `AzGuard::permission(...)`, чтобы избежать опечаток:

```php
use AzGuard\Facades\AzGuard;

// ✅ Нативный Gate — полный ключ с префиксом панели (выведен из enum)
Gate::allows(AzGuard::permission('app', PostsPermission::View), $post);
Gate::authorize(AzGuard::permission('app', PostsPermission::Edit), $post);

// ✅ Либо просто полный ключ, когда так читается понятнее
Gate::allows('app.posts.view', $post);

// ✅ Директива AzGuard — понимает enum, передавайте кейс напрямую
@azcan(PostsPermission::View) ... @endazcan

// ✅ Проверка через трейт — тоже enum-aware
$user->hasPermission(PostsPermission::View);
```
