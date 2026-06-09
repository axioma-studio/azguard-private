# Best Practices

Guidelines for building a clean, maintainable authorization layer with AzGuard.

## Roles vs Permissions

**Permissions** are the atomic units that gate individual actions. **Roles** bundle permissions into a named set. Always design permissions first, then group them into roles.

```php
// ✅ Business logic checks the permission
if ($user->hasPermission(DocumentsPermission::Edit)) {
    // ...
}

// ❌ Business logic checks the role — brittle
if ($user->hasRole('editor')) {
    // What if an editor loses this permission? Or a manager gets it?
}
```

**Rule:** Check permissions in application code. Use role names only in admin UI and seeding.

## One enum per resource

```php
// ✅ Good — focused enums
DocumentsPermission   // view, create, edit, delete, publish
InvoicesPermission    // view, create, export
UsersPermission       // view, invite, edit, deactivate

// ❌ Bad — one giant enum
AppPermission         // documents_view, documents_create, invoices_view, ...
```

## Never hardcode string keys

```php
// ❌ Typos are silent failures — no IDE warning, no static analysis error
$user->hasPermission('app.documents.veiw'); // typo — always returns false

// ✅ Type-safe, IDE-navigable, refactor-safe
$user->hasPermission(DocumentsPermission::View);
```

This rule applies **everywhere** — not just `hasPermission()`. See the section below.

## Enum constants in Gate and Blade

Pass enum cases directly to `Gate::allows()`, `Gate::authorize()`, and `$this->authorize()`. Laravel calls `->value` automatically on `BackedEnum`.

```php
// ✅ Controller / service / job
Gate::allows(DocumentsPermission::Edit, $document);
$this->authorize(DocumentsPermission::Delete, $document);

if (! Gate::allows(DocumentsPermission::Edit, $document)) {
    abort(403);
}

// ❌ Raw string — typo won't be caught by IDE or Larastan
Gate::allows('app.documents.edti', $document);
```

In **Blade templates** (no `use` statements), use one of two patterns:

```php
// ✅ Option 1 (preferred): resolve in the controller, pass as boolean
public function show(Document $document): Response
{
    return view('documents.show', [
        'document' => $document,
        'can' => [
            'edit'   => Gate::allows(DocumentsPermission::Edit,   $document),
            'delete' => Gate::allows(DocumentsPermission::Delete, $document),
        ],
    ]);
}
```

```blade
{{-- ✅ Option 1: clean, no strings in template --}}
@if($can['edit'])
    <button>Edit</button>
@endif

{{-- ✅ Option 2: FQCN with ->value when a variable isn't available --}}
@can(\App\AzGuard\App\Permissions\DocumentsPermission::Edit->value)
    <button>Edit</button>
@endcan

{{-- ❌ Raw string — avoid --}}
@can('app.documents.edit')
    <button>Edit</button>
@endcan
```

::: tip Route middleware — the one exception
`Route::middleware('can:...')` requires a string. Derive it from the enum:
```php
->middleware('can:' . DocumentsPermission::Edit->value . ',document')
```
:::

## Static roles as the source of truth

Static PHP roles are version-controlled. Use DB roles only when end-users need to configure permissions at runtime (multi-tenant SaaS, admin-defined roles).

```
✅ Static:  EditorRole, AdminRole, ViewerRole  — in Git
✅ Dynamic: "tenant-admin", custom roles       — created at runtime by admins
❌ Dynamic: replacing a static EditorRole with a DB record with no code equivalent
```

## Direct grants for exceptions, not new roles

```php
// ✅ Temporary exception via grant
(new GrantBuilder($user))
    ->on('app')
    ->give(DocumentsPermission::Delete)
    ->until(now()->addWeek());

// ❌ Role created just for one person
$role = DynamicRole::create(['name' => 'john-can-delete', ...]);
```

## Model Policies

Use Laravel Policies for **object-level** checks (can this user edit *this* document?). Use AzGuard permissions for **capability-level** checks (can this user edit documents at all?).

```php
class DocumentPolicy
{
    public function edit(User $user, Document $document): bool
    {
        // 1. Capability check — enum, not string
        if (! $user->hasPermission(DocumentsPermission::Edit)) {
            return false;
        }
        // 2. Object ownership check
        return $user->id === $document->user_id || $user->hasRole('manager');
    }
}
```

When you pass `arguments: ['document']` to `#[CheckPermission]`, AzGuard passes the model to Gate which runs the policy.

## Performance Tips

- **Cache is on by default.** Permission resolution is O(1) after the first request. Don't disable it in production.
- **Flush specifically.** Use `$user->flushPermissions()` instead of `cache:clear` — it only clears the affected user's cache entry.
- **Use `azguard.roles` middleware.** It loads all roles and grants in a single query per request. Removing it causes N+1 queries when `hasPermission()` is called.
- **Avoid per-item checks in loops.** If you're checking permissions for many users, load roles eagerly with `$users->load('roles')`.

## Naming Conventions

| Thing | Convention | Example |
|---|---|---|
| Permission enum | `{Resource}Permission` | `DocumentsPermission` |
| Enum case | PascalCase verb | `View`, `Create`, `Edit`, `Delete`, `Export` |
| Role class | `{Name}Role` | `EditorRole`, `ViewerRole` |
| Role name (string) | kebab-case | `'editor'`, `'super-admin'` |
| Panel ID | lowercase, short | `'app'`, `'admin'`, `'api'` |
