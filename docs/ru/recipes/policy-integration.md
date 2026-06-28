# Интеграция с Policy

Как совместить AzGuard с Laravel Policy для тонкого контроля доступа.

## Базовая интеграция

```php
// app/Policies/PostPolicy.php
class PostPolicy
{
    // Просто делегируем AzGuard
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PostsPermission::View);
    }

    // Комбинируем право + бизнес-логику
    public function update(User $user, Post $post): bool
    {
        if (!$user->hasPermission(PostsPermission::Edit)) {
            return false;
        }

        // Редактор может изменять только свои посты
        // Администратор — любые
        return $user->hasRole('admin') || $post->user_id === $user->id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->hasPermission(PostsPermission::Delete)
            && !$post->is_published; // нельзя удалить опубликованный
    }
}
```

## Before-хук в Policy

```php
class PostPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        // Супер-admin обходит все проверки Policy
        if ($user->is_super_admin) {
            return true;
        }
        return null;
    }
}
```
