# Permissions

Permissions in AzGuard are **PHP enum cases**, not database records. They live in your codebase, are reviewed in PRs, and are always in sync with your application logic.

## Naming convention

Every permission key follows the pattern `{panel}.{resource}.{action}`:

```
app.documents.view
app.documents.create
admin.users.delete
api.reports.export
```

The panel prefix (`app.`) is added automatically by AzGuard based on the panel the enum is registered in. Inside your enum you only declare `documents.view`.

## Defining permissions

```php
use AzGuard\Contracts\PermissionInterface;
use AzGuard\Attributes\GateAbility;
use AzGuard\Attributes\RoleOnly;

enum DocumentsPermission: string implements PermissionInterface
{
    // Registered with Laravel Gate — usable via Gate::allows(), @can, policies
    #[GateAbility]
    case View   = 'documents.view';

    #[GateAbility]
    case Create = 'documents.create';

    #[GateAbility]
    case Edit   = 'documents.edit';

    // Not registered with Gate — checked only via hasPermission()
    // Use for internal checks that should not be exposed through Gate
    #[RoleOnly]
    case Delete = 'documents.delete';
}
```

## Attributes

| Attribute | Effect |
|---|---|
| `#[GateAbility]` | Registers the permission with Laravel Gate as `{panel}.{value}` |
| `#[RoleOnly]` | Permission exists in the catalog but is **not** registered with Gate |
| _(none)_ | Same as `#[GateAbility]` — explicit is always better |

## Generate via Artisan

```bash
php artisan azguard:make-permission {Panel} {ClassName}

php artisan azguard:make-permission App DocumentsPermission
php artisan azguard:make-permission Admin UsersPermission
```

The command creates the file in `app/AzGuard/{Panel}/Permissions/` and reminds you to register it in your panel provider.

## Checking permissions

```php
// Via HasAzGuard trait — accepts enum case or full string key
$user->hasPermission(DocumentsPermission::View);
$user->hasPermission('app.documents.view');

// Via Gate (requires #[GateAbility])
Gate::allows('app.documents.view');
$this->authorize('app.documents.view');

// Blade
@can('app.documents.view')
    ...
@endcan

// Silent check — returns false instead of throwing, useful in conditions
$user->checkPermission(DocumentsPermission::View);
```

## Inspecting what a user has

```php
// All permissions from all roles + direct grants
$user->getAllPermissions();           // Collection of permission strings

// Only permissions from roles (not direct grants)
$user->getPermissionsViaRoles();      // Collection

// Only direct grants
$user->getDirectPermissions();        // Collection (requires HasDirectGrants)

// Permission keys as plain strings
$user->getPermissionNames();          // Collection<string>

// Check if a specific permission is in the collection
$user->getAllPermissions()->contains('app.documents.view');
```

## Assigning permissions to a role

In static roles, permissions are declared directly in code:

```php
public function permissions(): array
{
    return [
        DocumentsPermission::View,
        DocumentsPermission::Create,
        DocumentsPermission::Edit,
    ];
}
```

For dynamic (DB-backed) roles, use the model API:

```php
use AzGuard\Models\DynamicRole;

$role = DynamicRole::where('name', 'editor')->first();

// Add permissions
$role->givePermissions([
    DocumentsPermission::View,
    DocumentsPermission::Create,
]);

// Sync permissions (replaces existing list)
$role->syncPermissions([
    DocumentsPermission::View,
    DocumentsPermission::Edit,
]);

// Remove a single permission
$role->revokePermission(DocumentsPermission::Create);

// Get all permissions on this role
$role->getPermissions();  // Collection
```

## Gotchas

**Permission keys are panel-scoped.** `documents.view` and `app.documents.view` are the same permission if the enum is registered under the `app` panel. Using the string form requires the full key including the panel prefix.

**`#[RoleOnly]` permissions don't work with `Gate::allows()`.** They are only resolvable via `$user->hasPermission(...)`. If you try to use them in a policy or `@can`, Gate will not find them.

**Don't mix string keys and enum cases carelessly.** Always use enum cases in code (`DocumentsPermission::View`), and reserve string keys for places where enums aren't available (e.g., config files, migrations, Artisan commands).

## TypeScript export

AzGuard exports all registered permissions to a TypeScript constants file for your frontend:

```bash
php artisan azguard:export-ts
# outputs: resources/js/permissions.ts
```

```typescript
// resources/js/permissions.ts (auto-generated, do not edit)
export const Permissions = {
  app: {
    documents: {
      view:   'app.documents.view',
      create: 'app.documents.create',
      edit:   'app.documents.edit',
      delete: 'app.documents.delete',
    },
  },
} as const;
```

See [Frontend Abilities](./abilities-frontend.md) for Inertia/Vue/React integration.

## Listing all permissions

```bash
php artisan azguard:list-permissions
php artisan azguard:list-permissions --panel=app
```

## Best practices

- **One enum per resource.** `DocumentsPermission`, `UsersPermission`, `ReportsPermission` — not one giant `AppPermission` enum.
- **CRUD as the default set.** `view`, `create`, `edit`, `delete`. Add extras only as needed: `export`, `publish`, `approve`.
- **Always use `#[GateAbility]` explicitly**, even though it's the default. It makes intent clear during code review.
- **Never hardcode string keys in PHP.** Always reference enum cases: `DocumentsPermission::View`, not `'app.documents.view'`.
- **Keep enum cases descriptive but terse.** `case Export` is fine; `case ExportToCsvForExternalTeams` is not.
