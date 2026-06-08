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
            && ($post->user_id === $user->id || $user->hasRole('publisher'));
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
