# HTTP и Middleware

## Регистрация middleware

AzGuard автоматически регистрирует middleware `azguard` через Service Provider. В Laravel 11+ он добавляется в `bootstrap/app.php` автоматически.

```php
// Для Laravel 10 и старше — вручную в Kernel.php
protected $routeMiddleware = [
    'azguard' => \AzGuard\Http\Middleware\CheckPermission::class,
];
```

## Использование в маршрутах

```php
// Одно разрешение
Route::middleware('azguard:app.posts.edit')
    ->put('/posts/{post}', [PostController::class, 'update']);

// Несколько разрешений (И — требуются все)
Route::middleware('azguard:app.posts.edit,app.posts.publish')
    ->post('/posts/{post}/publish', [PostController::class, 'publish']);

// Группа маршрутов
Route::middleware(['auth', 'azguard:app.admin.access'])
    ->prefix('/admin')
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index']);
        Route::resource('/users', AdminUserController::class);
    });
```

## Атрибут CheckPermission

```php
use AzGuard\Attributes\CheckPermission;

class PostController extends Controller
{
    #[CheckPermission(PostsPermission::View)]
    public function index(): Response { ... }

    #[CheckPermission(PostsPermission::Create)]
    public function store(StorePostRequest $request): Response { ... }

    // С model binding — автоматически передаётся в Policy
    #[CheckPermission(permission: PostsPermission::Edit, arguments: ['post'])]
    public function update(Request $request, Post $post): Response { ... }
}
```

## Ответ на отказ в доступе

По умолчанию возвращается `403 Forbidden`. Переопределите поведение:

```php
// app/Exceptions/Handler.php
protected function unauthenticated($request, AuthenticationException $exception): Response
{
    if ($request->expectsJson()) {
        return response()->json(['message' => 'Доступ запрещён.'], 403);
    }
    return redirect()->route('login');
}
```
