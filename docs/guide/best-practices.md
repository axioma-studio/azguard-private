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
// ❌ Typos are silent failures
$user->hasPermission('app.documents.veiw'); // typo

// ✅ Type-safe, IDE-navigable, refactor-safe
$user->hasPermission(DocumentsPermission::View);
```

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
        // 1. Capability check
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
