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
});
```

| Middleware | Alias | Purpose |
|---|---|---|
| `AzGuard\Http\Middleware\SetAzGuardPanel` | `azguard.panel` | Sets the active panel for this request |
| `AzGuard\Http\Middleware\LoadAzGuardRoles` | `azguard.roles` | Loads and caches roles + grants for `auth()->user()` |
| `AzGuard\Http\Middleware\CheckAccessMiddleware` | `check.access` | Reads `#[CheckPermission]` and calls `Gate::allows()` |

All three middleware aliases are registered automatically by `AzGuardServiceProvider`.

## `#[CheckPermission]`

```php
use AzGuard\Attributes\CheckPermission;
use AzGuard\Attributes\SkipGuardCheck;

class DocumentController extends Controller
{
    // Standard: check view permission, no model argument
    #[CheckPermission(DocumentsPermission::View)]
    public function index(): Response
    {
        return Inertia::render('Documents/Index');
    }

    // With model argument — passed to Gate::allows() for policy integration
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

For non-controller code (jobs, listeners, services), call the Gate directly:

```php
if (! Gate::allows('app.documents.edit', $document)) {
    throw new AuthorizationException();
}
```

Or use `$this->authorize()` inside controllers:

```php
$this->authorize('app.documents.delete', $document);
```

::: tip
`$this->authorize()` is fine, but `#[CheckPermission]` is the primary pattern — it keeps authorization out of the method body and makes it visible in route inspection tools.
:::

## API routes (JSON 403)

For API routes, Laravel returns JSON `{"message": "This action is unauthorized."}` with `403` by default when `$this->authorize()` or `Gate::authorize()` throws. For `check.access` middleware:

```php
// Customize the response in app/Exceptions/Handler.php
public function render($request, Throwable $e)
{
    if ($e instanceof AuthorizationException && $request->expectsJson()) {
        return response()->json(['error' => 'Forbidden'], 403);
    }

    return parent::render($request, $e);
}
```

## Protecting entire route groups

To require a specific role (not just authentication) on every route in a group:

```php
Route::middleware([
    'auth',
    'azguard.panel:app',
    'azguard.roles',
    'check.access',
    function ($request, $next) {
        if (! $request->user()->hasRole('editor')) {
            abort(403);
        }
        return $next($request);
    },
])->group(function () {
    // Only editors reach here
});
```

Or use a dedicated middleware:

```php
// app/Http/Middleware/RequireEditorRole.php
public function handle(Request $request, Closure $next, string $role = 'editor'): Response
{
    if (! $request->user()?->hasRole($role)) {
        abort(403);
    }
    return $next($request);
}
```
