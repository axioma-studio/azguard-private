# HTTP и Middleware

## Встроенный middleware

AzGuard регистрирует middleware `azguard` автоматически через Service Provider.

```php
// routes/web.php
Route::middleware(['auth', 'azguard:app.posts.edit'])
    ->put('/posts/{post}', [PostController::class, 'update']);

// Несколько прав (AND)
Route::middleware(['auth', 'azguard:app.posts.edit,app.posts.publish'])
    ->post('/posts/{post}/publish', ...);
```

## Атрибут #[CheckPermission]

```php
use AzGuard\Attributes\CheckPermission;

class PostController extends Controller
{
    #[CheckPermission(PostsPermission::View)]
    public function index(): Response { ... }

    #[CheckPermission(PostsPermission::Create)]
    public function store(StorePostRequest $request): Response { ... }

    // С model binding — маршрутится через Policy
    #[CheckPermission(permission: PostsPermission::Edit, arguments: ['post'])]
    public function update(UpdatePostRequest $request, Post $post): Response { ... }
}
```

## Обработка 403

По умолчанию AzGuard бросает `AzGuard\Exceptions\UnauthorizedException`. Обработайте её глобально:

```php
// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (UnauthorizedException $e, Request $request) {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return redirect()->route('home')->with('error', 'Доступ запрещён');
    });
})
```

## Inertia + SharedData

```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'abilities' => fn () => $request->user()
            ? $request->user()->abilities()   // массив активных прав
            : [],
    ];
}
```
