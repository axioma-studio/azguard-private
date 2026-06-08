# Entity Scopes

Entity Scopes ограничивают видимость данных на уровне запроса к БД — в зависимости от ролей и прав пользователя.

## Базовый пример

```php
use AzGuard\Scopes\AzGuardScope;

class Post extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new AzGuardScope(
            permission: PostsPermission::ViewAll,
            fallback: fn ($query) => $query->where('author_id', auth()->id()),
        ));
    }
}
```

Если у пользователя есть `PostsPermission::ViewAll` — он видит все посты. Иначе — только свои.

## Ручной scope

```php
// Только в методах контроллера
public function index(): Response
{
    $posts = Post::query()
        ->when(
            !auth()->user()->hasPermission(PostsPermission::ViewAll),
            fn ($q) => $q->where('author_id', auth()->id())
        )
        ->paginate();

    return Inertia::render('Posts/Index', compact('posts'));
}
```

## Scope по команде

```php
Post::query()
    ->forTeam($user->team_id)  // кастомный scope
    ->when(
        $user->hasPermission(PostsPermission::ViewAll),
        fn ($q) => $q, // без ограничений
        fn ($q) => $q->where('author_id', $user->id)
    )
    ->get();
```
