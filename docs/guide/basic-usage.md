# Basic Usage

This page walks you through the three core concepts you'll use every day: **permissions**, **roles**, and **checking access**.

## Checking permissions

Once a user has a role assigned, you can check their permissions in several ways:

```php
// On the User model
$user->hasPermission(DocumentsPermission::View);   // true / false
$user->can('app.documents.view');                   // Laravel Gate

// From the Gate facade
use Illuminate\Support\Facades\Gate;

Gate::allows('app.documents.view');                 // true / false
Gate::check('app.documents.view');                  // alias

// In a controller
$this->authorize('app.documents.view');             // throws 403
```

## Checking roles

```php
$user->hasRole(EditorRole::class);         // true / false
$user->hasAnyRole([EditorRole::class, AdminRole::class]);
$user->hasAllRoles([EditorRole::class, ModeratorRole::class]);
```

## Assigning roles

```php
// Assign
$user->assignRole(EditorRole::class);

// Remove
$user->removeRole(EditorRole::class);

// Sync (replaces all current roles)
$user->syncRoles([EditorRole::class, ModeratorRole::class]);
```

## Getting all permissions

```php
// All permissions resolved from all assigned roles
$user->getAllPermissions();  // Collection of permission strings

// All roles assigned to this user
$user->getRoles();           // Collection of role class strings
```

## Direct permission checks via Gate

Because AzGuard plugs into `Gate::before()`, every standard Laravel authorization helper works out of the box:

```php
// Middleware
Route::get('/documents', DocumentController::class)
    ->middleware('can:app.documents.view');

// Blade
@can('app.documents.view')
    <a href="{{ route('documents.index') }}">Documents</a>
@endcan

@cannot('app.documents.delete')
    <span class="text-gray-400">Delete (no access)</span>
@endcannot

// Policy
public function view(User $user, Document $document): bool
{
    return $user->hasPermission(DocumentsPermission::View);
}
```

::: tip Next steps
Dig into [Permissions](./permissions.md), [Roles](./roles.md), and [Direct Grants](./direct-grants.md) for the full API.
:::
