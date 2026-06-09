# HTTP и Middleware

## Доступные Middleware

| Middleware | Описание |
|---|---|
| `azguard.permission` | Проверка одного права |
| `azguard.role` | Проверка одной роли |
| `azguard.panel` | Устанавливает активную панель для запроса |

## Примеры применения

```php
// routes/web.php
Route::get('/reports/export', [ReportController::class, 'export'])
    ->middleware('azguard.permission:app.reports.export');

Route::get('/admin', [AdminController::class, 'index'])
    ->middleware(['auth', 'azguard.role:' . AdminRole::class]);

// Группа роутов с общим middleware
Route::middleware(['auth', 'azguard.panel:admin'])->group(function () {
    Route::resource('users', UserController::class);
});
```

## Атрибут `#[CheckPermission]` вс контроллере

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

```php
// app/Exceptions/Handler.php
public function register(): void
{
    $this->renderable(function (PermissionDeniedException $e, Request $request) {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Доступ запрещён.',
                'required' => $e->getPermission(),
            ], 403);
        }
        return redirect()->route('home')->with('error', 'Недостаточно прав.');
    });
}
```

→ [Исключения](/ru/guide/exceptions) · [Несколько Guards](/ru/guide/multiple-guards)
