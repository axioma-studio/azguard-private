# Basic Usage

This page walks you through the complete daily workflow: defining roles and permissions, assigning them to users, checking access, and querying users by role or permission.

## Add the trait

The only model-level requirement is the `HasAzGuard` trait on your `User` (or any authenticatable model):

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use AzGuard\Traits\HasAzGuard;

class User extends Authenticatable
{
    use HasAzGuard;
}
```

See [Prerequisites](./prerequisites.md) for compatibility requirements and reserved property names.

## Define a permission enum

```php
// app/AzGuard/App/Permissions/DocumentsPermission.php
use AzGuard\Contracts\PermissionInterface;
use AzGuard\Attributes\GateAbility;

enum DocumentsPermission: string implements PermissionInterface
{
    #[GateAbility]
    case View   = 'documents.view';

    #[GateAbility]
    case Create = 'documents.create';

    #[GateAbility]
    case Edit   = 'documents.edit';

    #[GateAbility]
    case Delete = 'documents.delete';
}
```

AzGuard registers each `#[GateAbility]` case with Laravel Gate as `{panel}.{value}`, e.g. `app.documents.view`.

See [Permissions](./permissions.md) for the full attribute reference.

## Define a role class

```php
// app/AzGuard/App/Roles/EditorRole.php
use AzGuard\Contracts\RoleInterface;
use App\AzGuard\App\Permissions\DocumentsPermission;

class EditorRole implements RoleInterface
{
    public function getName(): string  { return 'editor'; }
    public function getPanel(): string { return 'app'; }
    public function getLevel(): int    { return 10; }

    public function permissions(): array
    {
        return [
            DocumentsPermission::View,
            DocumentsPermission::Create,
            DocumentsPermission::Edit,
        ];
    }
}
```

See [Roles](./roles.md) for the full role API including dynamic (DB-backed) roles.

## Assign a role to a user

```php
// By class
$user->assignRole(EditorRole::class);

// By string name
$user->assignRole('editor');

// Multiple roles at once
$user->assignRole([EditorRole::class, ViewerRole::class]);
```

## Remove a role

```php
$user->removeRole(EditorRole::class);
$user->removeRole('editor');
```

## Sync roles (replace all)

`syncRoles` replaces the user's current role set with the given list. Roles not in the list are removed:

```php
// Replace all roles with editor + viewer
$user->syncRoles([EditorRole::class, ViewerRole::class]);

// Remove all roles
$user->syncRoles([]);
```

## Check permissions

```php
// Via the trait
$user->hasPermission(DocumentsPermission::View);   // enum case — preferred
$user->hasPermission('app.documents.view');         // full key string

// Via Laravel Gate (requires #[GateAbility])
Gate::allows('app.documents.view');
$this->authorize('app.documents.view');             // throws 403 on failure

// Blade
@can('app.documents.view')
    <a href="/documents">Documents</a>
@endcan

// Route middleware
Route::get('/documents', DocumentController::class)
    ->middleware('can:app.documents.view');
```

## Check roles

```php
$user->hasRole(EditorRole::class);                  // bool
$user->hasRole('editor');                           // bool — string form
$user->hasAnyRole([EditorRole::class, AdminRole::class]);  // any match
$user->hasAllRoles([EditorRole::class, ModeratorRole::class]); // must have all
```

::: warning Prefer permission checks over role checks
Checking a **permission** is safer than checking a **role**. Roles change — a user might get promoted to a new role with different permissions. Permissions stay semantically stable.

```php
// Fragile — role name may change or a new role may be introduced
if ($user->hasRole('editor')) { ... }

// Robust — the permission intent never changes
if ($user->hasPermission(DocumentsPermission::Edit)) { ... }
```
:::

## Inspect a user's permissions and roles

```php
// All permissions resolved from all assigned roles (+ any direct grants)
$user->getAllPermissions();       // Collection of permission strings

// Only permissions granted directly (not via a role)
$user->getDirectPermissions();    // Collection of permission strings

// Only permissions coming from roles
$user->getPermissionsViaRoles();  // Collection of permission strings

// All role names
$user->getRoleNames();            // Collection<string>

// All role class strings
$user->getRoles();                // Collection<string>
```

## Query users by role or permission

AzGuard provides Eloquent scopes to filter users:

```php
// Users that have the editor role
User::role(EditorRole::class)->get();
User::role('editor')->get();

// Users that do NOT have the editor role
User::withoutRole('editor')->get();

// Users that have a specific permission (directly or via a role)
User::permission(DocumentsPermission::Edit)->get();
User::permission('app.documents.edit')->get();

// Users that do NOT have a specific permission
User::withoutPermission('app.documents.delete')->get();

// Scopes accept a string, an array, or a Collection
User::role(['editor', 'viewer'])->get();
```

## Eloquent relationship helpers

```php
// Eager-load roles with users (avoids N+1)
$users = User::with('azRoles')->get();

// Users who have no roles at all
$users = User::doesntHave('azRoles')->get();

// Count users per role
User::with('azRoles')
    ->get()
    ->groupBy(fn ($u) => $u->getRoleNames()->first() ?? 'no role')
    ->map->count();
```

::: tip Next steps
- [Permissions](./permissions.md) — full attribute and enum API
- [Roles](./roles.md) — static and dynamic roles, role levels
- [Direct Grants](./direct-grants.md) — per-user permission overrides
- [HTTP Access](./http-access.md) — middleware and route protection
- [Blade Directives](./blade-directives.md) — `@can`, `@role`, and custom directives
:::
