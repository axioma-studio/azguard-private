# Basic Usage

This page walks you through the full lifecycle: defining roles and permissions, assigning them to users, checking access, and querying users by role or permission.

## Add the trait

Add `HasAzGuard` to every model that needs role/permission checks:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable
{
    use HasAzGuard;
}
```

::: tip
See [Prerequisites](/guide/prerequisites) for important constraints — especially the list of reserved property names.
:::

## Assign a role to a user

```php
// Single role
$user->assignRole(EditorRole::class);
$user->assignRole('editor');              // by name

// Multiple roles
$user->assignRole([EditorRole::class, ViewerRole::class]);

// Replace all roles
$user->syncRoles([EditorRole::class]);

// Remove one role
$user->removeRole(EditorRole::class);

// Remove all roles
$user->syncRoles([]);
```

## Give a permission directly to a role

Most of the time you define permissions inside the role class. But for `DynamicRole` you manage them at runtime:

```php
$role->givePermissions([DocumentsPermission::View, DocumentsPermission::Create]);
$role->syncPermissions([DocumentsPermission::View]);
$role->revokePermission(DocumentsPermission::Create);
```

See [Direct Grants](/guide/direct-grants) for granting permissions directly to a *user*, bypassing roles.

## Check permissions

```php
// Via trait
$user->hasPermission(DocumentsPermission::View);   // enum case — preferred
$user->hasPermission('app.documents.view');         // full key string

// Via Laravel Gate (requires #[GateAbility] on the enum case)
Gate::allows('app.documents.view');
Gate::check('app.documents.view');                 // alias

// In a controller
$this->authorize('app.documents.view');            // throws 403 on failure

// Route middleware
Route::get('/documents', DocumentController::class)
    ->middleware('can:app.documents.view');
```

## Check roles

```php
$user->hasRole(EditorRole::class);                  // bool
$user->hasRole('editor');                           // by name
$user->hasAnyRole([EditorRole::class, AdminRole::class]);  // true if any
$user->hasAllRoles([EditorRole::class, ModeratorRole::class]); // true if all
```

## Inspect assigned roles and permissions

```php
// All roles assigned to the user
$user->getRoleNames();              // Collection<string> e.g. ['editor', 'viewer']
$user->getRoles();                  // Collection of role class strings

// All permissions the user has (resolved from all assigned roles + direct grants)
$user->getAllPermissions();         // Collection<string>

// Only direct grants (not via roles)
$user->getDirectPermissions();      // Collection<string>

// Only permissions coming through roles
$user->getPermissionsViaRoles();    // Collection<string>

// Check specific permission origin
$user->hasDirectPermission(DocumentsPermission::View);    // bool
$user->hasPermissionViaRole(DocumentsPermission::View);   // bool
```

## Query scopes

AzGuard adds Eloquent query scopes to any model using `HasAzGuard`:

```php
// Users who have a specific role
User::role('editor')->get();
User::role(EditorRole::class)->get();
User::role(['editor', 'admin'])->get();   // any of these roles

// Users who do NOT have a specific role
User::withoutRole('editor')->get();

// Users who have a specific permission (via any role or direct)
User::permission('app.documents.view')->get();
User::permission(DocumentsPermission::View)->get();

// Users who do NOT have a specific permission
User::withoutPermission(DocumentsPermission::View)->get();
```

Scopes can be chained with other Eloquent calls:

```php
User::role('editor')
    ->where('active', true)
    ->orderBy('name')
    ->get();
```

## Eager loading

Avoid N+1 queries when working with collections:

```php
// Load roles with users
$users = User::with('roles')->get();

// Load direct permission grants with users
$users = User::with('directPermissions')->get();

// Both
$users = User::with(['roles', 'directPermissions'])->get();
```

## Counting users with a role

```php
// Count users having a role
$editorCount = User::role('editor')->count();

// Count users NOT having any role assigned
$noRoleCount = User::doesntHave('roles')->count();

// Users with role, grouped by role name
$counts = User::with('roles')
    ->get()
    ->flatMap->roles
    ->countBy('name');
// => ['editor' => 12, 'admin' => 3, 'viewer' => 47]
```

## Guard name

If your application uses multiple authentication guards (e.g. `web`, `api`, `admin`), roles and permissions are always scoped to the guard of the model being checked. You rarely need to think about this — AzGuard resolves the guard automatically.

For cases where you need to check a different guard explicitly:

```php
$user->hasPermission(DocumentsPermission::View, guard: 'api');
$user->hasRole('editor', guard: 'admin');
```

See [Multiple Guards](/guide/multiple-guards) for full setup.

## Next steps

- [Permissions](/guide/permissions) — define and manage enum-based permissions
- [Roles](/guide/roles) — static and dynamic roles
- [Direct Grants](/guide/direct-grants) — per-user permission exceptions
- [Blade Directives](/guide/blade-directives) — `@can`, `@role`, `@hasrole` and friends
