# HTTP Access

AzGuard provides a declarative, attribute-based way to protect controller actions. Middleware handles the panel context; `#[CheckPermission]` handles the per-action check.

## Middleware stack

```php
Route::middleware([
    'azguard.panel:app',  // sets current panel → resolves permissions as app.*
    'azguard.roles',      // eager-loads the user's roles & direct grants
    'check.access',       // reads #[CheckPermission] on the controller method
])->group(function () {
    Route::apiResource('documents', DocumentController::class);
    Route::apiResource('reports', ReportController::class);
});
```

| Middleware | Alias | Purpose |
|---|---|---|
| `SetAzGuardPanel` | `azguard.panel` | Sets the active panel for this request |
| `LoadAzGuardRoles` | `azguard.roles` | Loads and caches roles + grants for `auth()->user()` |
| `CheckAccessMiddleware` | `check.access` | Reads `#[CheckPermission]` and calls `Gate::allows()` |

All three aliases are registered automatically by `AzGuardServiceProvider`.

## `#[CheckPermission]`

```php
use AzGuard\Attributes\CheckPermission;
use AzGuard\Attributes\SkipGuardCheck;

class DocumentController extends Controller
{
    // Check view permission — no model binding
    #[CheckPermission(DocumentsPermission::View)]
    public function index(): Response
    {
        return Inertia::render('Documents/Index', [
            'documents' => Document::paginate(),
        ]);
    }

    // With model argument — passed to Gate::allows() for Policy integration
    #[CheckPermission(permission: DocumentsPermission::View, arguments: ['document'])]
    public function show(Document $document): Response
    {
        return Inertia::render('Documents/Show', [
            'document'  => $document,
            'abilities' => DocumentsAbilities::fromDocument($document)->toArray(),
        ]);
    }

    #[CheckPermission(permission: DocumentsPermission::Edit, arguments: ['document'])]
    public function update(UpdateDocumentRequest $request, Document $document): Response
    {
        $document->update($request->validated());
        return back()->with('success', 'Document updated.');
    }

    #[CheckPermission(DocumentsPermission::Delete)]
    public function destroy(Document $document): Response
    {
        $document->delete();
        return redirect()->route('documents.index');
    }

    // Skip the guard check entirely for public endpoints
    #[SkipGuardCheck]
    public function publicPreview(Document $document): Response
    {
        return Inertia::render('Documents/Preview', ['document' => $document]);
    }
}
```

The `arguments` array maps to route model bindings by parameter name. The middleware resolves them from the request and passes them to `Gate::allows($ability, [$model])`.

## Manual Gate checks

For non-controller code (jobs, listeners, services), always pass the **enum case** — never a raw string:

```php
// ✅ Enum — type-safe, IDE-navigable
Gate::authorize(DocumentsPermission::Edit, $document);

if (! Gate::allows(DocumentsPermission::Edit, $document)) {
    throw new AuthorizationException('Cannot edit this document.');
}

// In controllers
$this->authorize(DocumentsPermission::Delete, $document);
```

::: tip Route middleware — the only exception
Route middleware `'can:'` requires a string. Use `->value` to derive it from the enum:
```php
Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])
    ->middleware('can:' . DocumentsPermission::Edit->value . ',document');
```
:::

## API routes (JSON 403)

For API routes, customize the error response in your exception handler:

```php
// app/Exceptions/Handler.php (Laravel 10 style)
public function render($request, Throwable $e)
{
    if ($e instanceof AuthorizationException && $request->expectsJson()) {
        return response()->json([
            'message' => 'Forbidden.',
            'error'   => 'insufficient_permissions',
        ], 403);
    }

    return parent::render($request, $e);
}

// Laravel 11 — bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (AuthorizationException $e, Request $request) {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
    });
})
```

## Middleware chaining

Multiple panels in the same application — register each group with its own panel middleware:

```php
// App panel
Route::prefix('app')
    ->middleware(['auth', 'azguard.panel:app', 'azguard.roles', 'check.access'])
    ->group(base_path('routes/app.php'));

// Admin panel
Route::prefix('admin')
    ->middleware(['auth', 'azguard.panel:admin', 'azguard.roles', 'check.access'])
    ->group(base_path('routes/admin.php'));

// API (stateless)
Route::prefix('api')
    ->middleware(['auth:sanctum', 'azguard.panel:api', 'azguard.roles', 'check.access'])
    ->group(base_path('routes/api.php'));
```

## Named middleware groups

DRY up your route definitions by registering named groups:

```php
// bootstrap/app.php (Laravel 11)
->withMiddleware(function (Middleware $middleware) {
    $middleware->appendToGroup('app-guard', [
        \AzGuard\Http\Middleware\SetAzGuardPanel::class.':app',
        \AzGuard\Http\Middleware\LoadAzGuardRoles::class,
        \AzGuard\Http\Middleware\CheckAccessMiddleware::class,
    ]);

    $middleware->appendToGroup('admin-guard', [
        \AzGuard\Http\Middleware\SetAzGuardPanel::class.':admin',
        \AzGuard\Http\Middleware\LoadAzGuardRoles::class,
        \AzGuard\Http\Middleware\CheckAccessMiddleware::class,
    ]);
})
```

```php
// routes/web.php — clean and readable
Route::middleware(['auth', 'app-guard'])
    ->group(base_path('routes/app.php'));

Route::middleware(['auth', 'admin-guard'])
    ->group(base_path('routes/admin.php'));
```

## Protecting entire route groups by role

```php
// Dedicated middleware class
// app/Http/Middleware/RequireRole.php
public function handle(Request $request, Closure $next, string $role, string $panel = 'app'): Response
{
    if (! $request->user()?->hasRole($role, panel: $panel)) {
        abort(403);
    }
    return $next($request);
}
```

```php
Route::middleware(['auth', 'app-guard', 'role:manager,app'])
    ->group(function () {
        Route::get('/reports', ReportsController::class);
    });
```
