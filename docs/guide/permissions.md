# Permissions

Permissions in AzGuard are **PHP enum cases**, not database records. They live in your codebase, get reviewed in PRs, and are always in sync with your app logic.

## Naming convention

Every permission key follows the pattern `{panel}.{resource}.{action}`:

```
app.documents.view
app.documents.create
admin.users.delete
api.reports.export
```

The panel prefix (`app.`) is added automatically by AzGuard based on the panel the enum is registered in. Inside your enum you only write `documents.view`.

## Defining permissions

```php
use AzGuard\Contracts\PermissionInterface;
use AzGuard\Attributes\GateAbility;
use AzGuard\Attributes\RoleOnly;

enum DocumentsPermission: string implements PermissionInterface
{
    // Registered as a Gate ability — usable with Gate::allows(), @can, policies
    #[GateAbility]
    case View   = 'documents.view';

    #[GateAbility]
    case Create = 'documents.create';

    #[GateAbility]
    case Edit   = 'documents.edit';

    // Not registered as a Gate ability — only checked via hasPermission()
    // Use for internal checks that should not be exposed to Gate
    #[RoleOnly]
    case Delete = 'documents.delete';
}
```

## Attributes

| Attribute | Effect |
|---|---|
| `#[GateAbility]` | Registers the permission with Laravel Gate as `{panel}.{value}` |
| `#[RoleOnly]` | Permission exists in the catalog but is **not** registered with Gate |
| _(none)_ | Same as `#[GateAbility]` — explicit is better |

## Generate via Artisan

```bash
php artisan azguard:make-permission {Panel} {ClassName}

# Example:
php artisan azguard:make-permission App DocumentsPermission
php artisan azguard:make-permission Admin UsersPermission
```

The command creates the file in `app/AzGuard/{Panel}/Permissions/` and reminds you to register it in your panel provider.

## Checking permissions

```php
// Via HasAzGuard trait
$user->hasPermission(DocumentsPermission::View);      // enum case
$user->hasPermission('app.documents.view');            // full key string

// Via Gate (requires #[GateAbility])
Gate::allows('app.documents.view');
$this->authorize('app.documents.view');

// Blade
@can('app.documents.view')
    ...
@endcan

// Silent check (never throws, useful in conditions)
$user->checkPermission(DocumentsPermission::View);    // false instead of exception
```

## TypeScript export

AzGuard can export all registered permissions to a TypeScript constants file for use in your frontend:

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

See [Frontend Abilities](/guide/abilities-frontend) for how to use this with Inertia/Vue/React.

## Listing all permissions

```bash
php artisan azguard:list-permissions
php artisan azguard:list-permissions --panel=app
```

## Best practices

- **One enum per resource.** `DocumentsPermission`, `UsersPermission`, `ReportsPermission` — not one giant `AppPermission` enum.
- **CRUD as the default actions.** `view`, `create`, `edit`, `delete`. Add only what you need: `export`, `publish`, `approve`.
- **Use `#[GateAbility]` explicitly.** Even if it's the default, it makes intent clear during code review.
- **Never hardcode string keys.** Always reference enum cases: `DocumentsPermission::View`, not `'app.documents.view'`.
