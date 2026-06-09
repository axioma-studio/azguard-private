# Entity Scopes

Entity Scopes позволяют ограничивать права конкретными экземплярами модели — например, пользователь может редактировать только **свои** посты.

## Определение scope

```php
use AzGuard\Contracts\EntityScopeInterface;

class OwnedByUserScope implements EntityScopeInterface
{
    public function check(Authenticatable $user, Model $entity): bool
    {
        return $entity->user_id === $user->getAuthIdentifier();
    }
}
```

## Применение в политике

```php
class PostPolicy
{
    public function update(User $user, Post $post): bool
    {
        return $user->hasPermission(PostsPermission::Edit)
            && app(OwnedByUserScope::class)->check($user, $post);
    }
}
```

## В атрибуте

```php
#[CheckPermission(
    permission: PostsPermission::Edit,
    scope: OwnedByUserScope::class,
    arguments: ['post']
)]
public function update(Request $request, Post $post): Response
{
    // Scope проверен автоматически
}
```
