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

| Middleware | Purpose |
|---|---|
| `azguard.panel:{name}` | Sets the active panel for this request |
| `azguard.roles` | Loads and caches roles + grants for `auth()->user()` |
| `check.access` | Reads `#[CheckPermission]` and calls `Gate::allows()` |

## `#[CheckPermission]`

```php
use AzGuard\Attributes\CheckPermission;

class DocumentController extends Controller
{
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
        // ...
    }

    // Skip the guard check entirely for public endpoints
    #[SkipGuardCheck]
    public function index(): Response
    {
        return Inertia::render('Documents/Index');
    }
}
```

The `arguments` array maps to route model bindings. The middleware resolves them from the request and passes them to `Gate::allows(resolvedAbility, $args)`.

## Manual Gate checks

For non-controller code (jobs, listeners, services), call the Gate directly:

```php
if (! Gate::allows('app.documents.edit', $document)) {
    throw new AuthorizationException();
}
```

Or use `$this->authorize()` inside controllers if you prefer the exception-based style:

```php
$this->authorize('app.documents.delete', $document);
```

::: tip
`$this->authorize()` is fine, but `#[CheckPermission]` is the primary pattern — it keeps authorization out of the method body and makes it visible in route inspection tools.
:::
