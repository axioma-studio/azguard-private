# Политики и Gate

## AzGuard + Laravel Policy

AzGuard интегрируется с Policy через стандартный механизм `Gate::before()`.

```php
// app/Policies/PostPolicy.php
class PostPolicy
{
    public function update(User $user, Post $post): bool
    {
        // Проверяем право через AzGuard
        if (!$user->hasPermission(PostsPermission::Edit)) {
            return false;
        }
        // Дополнительная бизнес-логика
        return $user->id === $post->author_id || $user->hasRole(EditorRole::class);
    }
}
```

```php
// В контроллере
#[CheckPermission(permission: PostsPermission::Edit, arguments: ['post'])]
public function update(UpdatePostRequest $request, Post $post): Response
{
    $this->authorize('update', $post); // вызывает PostPolicy::update()
    $post->update($request->validated());
    return back();
}
```

## Явная проверка через Gate

```php
// Gate::allows — для не-HTTP контекстов (jobs, commands)
if (Gate::forUser($user)->allows('app.posts.delete')) {
    // ...
}

// Gate::authorize — бросает AuthorizationException
Gate::authorize('app.posts.publish');
```

## Когда Policy, а когда hasPermission

| Ситуация | Рекомендация |
|---|---|
| Право зависит только от роли | `hasPermission()` напрямую |
| Право зависит от владельца записи | Policy |
| Комбинация роли и бизнес-логики | Policy, внутри `hasPermission()` |
| REST API без модели | `hasPermission()` / middleware |
