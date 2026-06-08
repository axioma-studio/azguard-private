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

## Define a permission

Permissions in AzGuard are PHP enum cases, not strings in a database. Define one enum per resource:

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

See [Permissions](./permissions.md) for the full attribute reference.

## Define a role

Roles are PHP classes that declare which permissions they grant:

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

See [Roles](./roles.md) for dynamic (DB-backed) roles and level-based hierarchy.

## Assign / remove / sync roles

```php
// Assign one role
$user->assignRole(EditorRole::class);
$user->assignRole('editor');                     // by name
$user->assignRole('editor', panel: 'app');       // explicit panel

// Remove one role
$user->removeRole(EditorRole::class);
$user->removeRole('editor');

// Sync — replaces ALL current roles with the given list
$user->syncRoles([EditorRole::class]);
$user->syncRoles(['editor', 'viewer']);          // by name

// Remove all roles
$user->syncRoles([]);
```

## Check roles

```php
$user->hasRole('editor');                          // bool
$user->hasRole(EditorRole::class);                // bool
$user->hasAnyRole(['editor', 'admin']);            // true if user has at least one
$user->hasAllRoles(['editor', 'moderator']);       // true only if user has all
$user->getRoleNames();                             // Collection<string>
$user->getRoles();                                 // Collection of role class strings
```

## Check permissions

```php
// Via the trait — accepts enum case or full string key
$user->hasPermission(DocumentsPermission::View);
$user->hasPermission('app.documents.view');
$user->hasAnyPermission([DocumentsPermission::Edit, DocumentsPermission::Delete]);
$user->hasAllPermissions([DocumentsPermission::View, DocumentsPermission::Edit]);

// Via Laravel Gate
use Illuminate\Support\Facades\Gate;

Gate::allows('app.documents.view');      // bool
Gate::check('app.documents.view');       // alias

// In a controller action — throws 403 on failure
$this->authorize('app.documents.view');

// Middleware on a route
Route::get('/documents', DocumentController::class)
    ->middleware('can:app.documents.view');
```

::: tip Always check permissions, not roles
Prefer `$user->hasPermission(...)` or `Gate::allows(...)` over `$user->hasRole(...)` for access control. Roles change over time; permissions express intent and are stable.
:::

## Inspect what a user has

```php
// All permissions resolved across all assigned roles + direct grants
$user->getAllPermissions();          // Collection<string>

// Only permissions granted directly (not via roles)
$user->getDirectPermissions();       // Collection — see Direct Grants

// Only permissions coming from roles
$user->getPermissionsViaRoles();     // Collection<string>

// Permission keys as strings
$user->getPermissionNames();         // Collection<string>

// All roles
$user->getRoles();                   // Collection of role class strings
$user->getRoleNames();               // Collection<string>

// Check specific permission origin
$user->hasDirectPermission(DocumentsPermission::View);   // bool
$user->hasPermissionViaRole(DocumentsPermission::View);  // bool
```

## Query scopes

AzGuard adds Eloquent query scopes to any model using `HasAzGuard`:

```php
// Users that have a specific role
User::role('editor')->get();
User::role(EditorRole::class)->get();
User::role(['editor', 'admin'])->get();     // has any of these roles

// Users that do NOT have a specific role
User::withoutRole('editor')->get();
User::withoutRole(['editor', 'viewer'])->get();

// Users that have a specific permission (via any role or direct grant)
User::permission('app.documents.edit')->get();
User::permission(DocumentsPermission::Edit)->get();

// Users that do NOT have a specific permission
User::withoutPermission('app.documents.delete')->get();
```

Scopes can be chained with other Eloquent calls:

```php
User::role('editor')
    ->where('active', true)
    ->orderBy('name')
    ->paginate();
```

## Useful Eloquent patterns

```php
// Eager-load roles for a user list (avoids N+1)
User::with('azRoles')->paginate();

// Users with no roles at all
User::doesntHave('azRoles')->get();

// Count users per role
User::role('editor')->count();

// Count users per role, grouped
User::with('azRoles')
    ->get()
    ->flatMap->azRoles
    ->countBy('name');
// => ['editor' => 12, 'admin' => 3, 'viewer' => 47]
```

## Gate check in Blade

```blade
@can('app.documents.edit')
    <a href="{{ route('documents.edit', $document) }}">Edit</a>
@endcan

@cannot('app.documents.delete')
    <span class="text-muted">No delete access</span>
@endcannot

@canany(['app.documents.create', 'app.documents.edit'])
    <div class="editor-toolbar">...</div>
@endcanany
```

See [Blade Directives](./blade-directives.md) for role checks and custom directives.

::: tip Next steps
- [Permissions](./permissions.md) — defining enums, attributes, TypeScript export
- [Roles](./roles.md) — static vs dynamic roles, levels, artisan commands
- [Direct Grants](./direct-grants.md) — per-user permissions without roles
- [HTTP Access](./http-access.md) — middleware, route groups, policies
:::
