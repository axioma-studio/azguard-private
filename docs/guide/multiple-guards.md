# Multiple Guards

AzGuard supports applications that use more than one authentication guard — for example, a `web` guard for regular users and an `api` guard for mobile clients, or a separate `admin` guard for a back-office panel.

## How it works

Each guard resolves its own set of panels. The permission namespace (`app.*`, `admin.*`) is bound to a panel, and a panel is bound to one or more guards. This gives you full isolation with zero cross-contamination.

```
web guard   → App panel   → app.documents.view, app.posts.edit …
admin guard → Admin panel → admin.users.ban, admin.roles.manage …
```

## Configuration

Define a separate panel per guard in your `config/azguard.php`:

```php
'panels' => [
    'app' => [
        'guard'    => 'web',
        'prefix'   => 'app',
        'roles_path' => app_path('AzGuard/App/Roles'),
    ],
    'admin' => [
        'guard'    => 'admin',
        'prefix'   => 'admin',
        'roles_path' => app_path('AzGuard/Admin/Roles'),
    ],
],
```

## Checking permissions for a specific guard

```php
// Default: checks the authenticated user's active guard
$user->hasPermission(DocumentsPermission::View);

// Explicit guard override
$user->forGuard('admin')->hasPermission(AdminUsersPermission::Ban);
```

## Middleware with guards

```php
// Apply guard-specific permission check in routes
Route::middleware(['auth:admin', 'can:admin.users.ban'])
    ->group(function () {
        Route::post('/admin/users/{user}/ban', BanUserController::class);
    });
```

## Blade directives with guards

```blade
@can('admin.users.ban')
    <button>Ban user</button>
@endcan
```

Blade uses the currently authenticated guard automatically. If you need to check against a different guard, use the `@guard` helper or check in the controller.

::: tip
See [Panels](./panels.md) for the full panel configuration reference.
:::
