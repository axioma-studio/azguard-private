# Интеграция с Policy

## Совместное использование AzGuard и Policy

```php
// app/Policies/PostPolicy.php
class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PostsPermission::View);
    }

    public function update(User $user, Post $post): bool
    {
        if (!$user->hasPermission(PostsPermission::Edit)) {
            return false;
        }
        // Дополнительная бизнес-логика: редактор может редактировать только свои посты
        return $user->hasRole(AdminRole::class) || $user->id === $post->author_id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->hasPermission(PostsPermission::Delete)
            && !$post->isLocked();
    }

    public function forceDelete(User $user, Post $post): bool
    {
        return $user->hasRole(SuperAdminRole::class);
    }
}
```

## Регистрация

```php
// app/Providers/AuthServiceProvider.php
protected $policies = [
    Post::class => PostPolicy::class,
];
```

## Использование в контроллере

```php
class PostController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Post::class, 'post');
    }

    // authorizeResource автоматически вызывает Policy-методы:
    // index -> viewAny, show -> view, create -> create,
    // update -> update, destroy -> delete
}
```
