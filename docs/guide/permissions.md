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

AzGuard provides several methods for checking permissions. Use the right one for the context:

```php
// ── On the User model (via HasAzGuard) ────────────────────────────────────

// Returns true/false — the everyday check
$user->hasPermission(DocumentsPermission::View);
$user->hasPermission('app.documents.view');         // full string key also works

// Returns true if the user has ANY of the listed permissions
$user->hasAnyPermission([
    DocumentsPermission::Edit,
    DocumentsPermission::Delete,
]);

// Returns true only if the user has ALL listed permissions
$user->hasAllPermissions([
    DocumentsPermission::View,
    DocumentsPermission::Edit,
]);

// Silent check — never throws, returns false on missing or expired grants
$user->checkPermission(DocumentsPermission::View);
```

```php
// ── Via Laravel Gate (requires #[GateAbility]) ────────────────────────────

Gate::allows('app.documents.view');          // bool
Gate::check('app.documents.view');           // alias
$this->authorize('app.documents.view');      // throws AuthorizationException on deny
Gate::authorize('app.documents.view');       // same, usable outside controllers

// With a model (routes through your Policy)
Gate::allows('app.documents.edit', $document);
$this->authorize('app.documents.edit', $document);
```

```php
// ── In Blade ─────────────────────────────────────────────────────────────

@can('app.documents.edit')
    <a href="{{ route('documents.edit', $doc) }}">Edit</a>
@endcan

@cannot('app.documents.delete')
    <p>Read-only access.</p>
@endcannot

@canany(['app.documents.edit', 'app.documents.delete'])
    <div class="actions">...</div>
@endcanany
```

::: tip must-party permissions
When a feature requires **multiple permissions simultaneously** (e.g., both `View` and `Export`), use `hasAllPermissions()` rather than chaining two `hasPermission()` calls. It reads more clearly and short-circuits on the first miss.

```php
// ✅ Clear intent — user must have BOTH
if ($user->hasAllPermissions([ReportsPermission::View, ReportsPermission::Export])) {
    return $this->buildReport();
}

// ✅ At least one of these — "must party" style access
if ($user->hasAnyPermission([DocumentsPermission::Edit, DocumentsPermission::Delete])) {
    // show edit actions toolbar
}
```
:::

## Inspecting what a user has

```php
// All permissions (roles + direct grants combined)
$user->getAllPermissions();           // Collection<string>

// Only permissions from roles (no direct grants)
$user->getPermissionsViaRoles();      // Collection<string>

// Only direct grants
$user->getDirectPermissions();        // Collection (requires HasDirectGrants)

// Permission keys as plain strings
$user->getPermissionNames();          // Collection<string>

// Check containment
$user->getAllPermissions()->contains('app.documents.view');
```

## All permission-check methods at a glance

| Method | Returns | Throws? | Gate? |
|---|---|---|---|
| `hasPermission($perm)` | `bool` | No | No |
| `hasAnyPermission(array)` | `bool` | No | No |
| `hasAllPermissions(array)` | `bool` | No | No |
| `checkPermission($perm)` | `bool` | No | No |
| `Gate::allows($key)` | `bool` | No | Yes |
| `$this->authorize($key)` | `void` | Yes (403) | Yes |
| `Gate::authorize($key)` | `void` | Yes (403) | Yes |

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

// Sync permissions (replaces the full list)
$role->syncPermissions([
    DocumentsPermission::View,
    DocumentsPermission::Edit,
]);

// Remove a single permission
$role->revokePermission(DocumentsPermission::Create);

// Get all permissions on this role
$role->getPermissions();   // Collection
```

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

// Usage in Vue / React
import { Permissions } from '@/permissions';

if (page.props.can[Permissions.app.documents.edit]) {
    // show edit button
}
```

See [Frontend Abilities](./abilities-frontend.md) for Inertia / Vue / React integration.

## Listing all permissions

```bash
# All permissions across all panels
php artisan azguard:list-permissions

# Filter by panel
php artisan azguard:list-permissions --panel=app

# Show which roles carry each permission
php artisan azguard:list-permissions --with-roles
```

## Gotchas

**Permission keys are panel-scoped.** `documents.view` and `app.documents.view` are the same permission if the enum is registered under the `app` panel. Using the string form requires the full key including the panel prefix.

**`#[RoleOnly]` permissions don't work with `Gate::allows()`.** They are only resolvable via `$user->hasPermission(...)`. Using them in a policy or `@can` will always return false.

**Don't mix string keys and enum cases carelessly.** Always use enum cases in PHP code (`DocumentsPermission::View`). Reserve string keys for places where enums aren't available (config files, migrations, Artisan commands).

## Best practices

- **One enum per resource.** `DocumentsPermission`, `UsersPermission`, `ReportsPermission` — not one giant `AppPermission` enum.
- **CRUD as the default set.** `view`, `create`, `edit`, `delete`. Add extras as needed: `export`, `publish`, `approve`.
- **Always use `#[GateAbility]` explicitly**, even though it's the default. It makes intent clear during code review.
- **Never hardcode string keys in PHP.** Always reference enum cases.
- **Keep enum cases descriptive but terse.** `case Export` is fine; `case ExportToCsvForExternalTeams` is not.
