# HTTP и Middleware

## Доступные Middleware

| Middleware | Описание |
|---|---|
| `azguard.panel` | Устанавливает активную панель для запроса |
| `azguard.panel_check:panel,permission` | Устанавливает панель и проверяет одно право |
| `azguard.check` | Проверяет атрибуты `#[CheckPermission]` на методе контроллера |
| `azguard.grant` | Проверяет прямой грант |
| `azguard.roles` | Загружает роли пользователя в запрос |

## Примеры применения

```php
// routes/web.php
Route::get('/reports/export', [ReportController::class, 'export'])
    ->middleware('azguard.panel_check:app,app.reports.export');

Route::get('/admin', [AdminController::class, 'index'])
    ->middleware(['auth', 'azguard.panel:admin']);

// Группа роутов с общим middleware
Route::middleware(['auth', 'azguard.panel:admin'])->group(function () {
    Route::resource('users', UserController::class);
});
```

## Атрибут `#[CheckPermission]` в контроллере

Применяйте middleware `azguard.check` к роуту, чтобы атрибуты на методе проверялись автоматически.

```php
class PostController extends Controller
{
    #[CheckPermission(PostsPermission::View)]
    public function index(): Response
    {
        return response()->json(Post::paginate());
    }

    #[CheckPermission(permission: PostsPermission::Edit, arguments: ['post'])]
    public function update(UpdatePostRequest $request, Post $post): Response
    {
        $post->update($request->validated());
        return response()->json($post);
    }
}
```

## Обработка 403

Middleware и атрибуты используют стандартный `abort(403)` Laravel. Перехватить ответ можно через рендеринг `HttpException`:

```php
// bootstrap/app.php — withExceptions()
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

$exceptions->renderable(function (HttpException $e, Request $request) {
    if ($e->getStatusCode() === 403 && $request->expectsJson()) {
        return response()->json([
            'message' => 'Доступ запрещён.',
        ], 403);
    }
});
```

→ [Исключения](/ru/guide/advanced/exceptions) · [Несколько Guards](/ru/guide/basic-usage/multiple-guards)
