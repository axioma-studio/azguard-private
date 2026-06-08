# Entity-Scoped Roles

AzGuard supports assigning roles scoped to specific entities — for example,
giving a user the `editor` role only for a particular `Project` or `Team`.

This is distinct from global roles (assigned via `HasAzGuard::assignRole()`):
a scoped role applies only when checking permissions in the context of a given entity.

## Installation

Run the migration to add the `role_id` column to the `model_has_scopes` table:

```bash
php artisan migrate
```

## Setup

Add the `InteractsWithAzScopes` trait to any **Eloquent model** that should support
entity-level scope filtering (e.g. `Project`, `Document`):

```php
use AzGuard\Concerns\InteractsWithAzScopes;

class Project extends Model
{
    use InteractsWithAzScopes;
}
```

Your `User` model must already use `HasAzGuard`.

## Assigning a scoped role

```php
// Give $user the 'editor' role scoped to $project
$user->assignScopedRole('editor', $project);

// Or pass a Role instance directly
$user->assignScopedRole($editorRole, $project);
```

## Removing a scoped role

```php
$user->removeScopedRole('editor', $project);
```

## Checking a scoped role

```php
if ($user->hasScopedRole('editor', $project)) {
    // ...
}
```

## Checking a scoped permission

`hasScopedPermission()` checks permissions in the following order:

1. **SuperAdmin / global wildcard** (`*`) — always granted regardless of scope.
2. **Global roles** — permissions from roles assigned via `assignRole()`.
3. **Scoped roles** — permissions from roles assigned for the given entity.

```php
if ($user->hasScopedPermission('app.projects.edit', $project)) {
    // user can edit this specific project
}
```

## Use-cases

| Scenario | Scoped role |
|---|---|
| Multi-tenant projects | `editor` scoped to `Project` |
| Team management | `team-admin` scoped to `Team` |
| Document review | `reviewer` scoped to `Document` |
| Resource ownership | `owner` scoped to any Eloquent model |

## Notes

- Scoped roles do **not** replace global roles — they stack on top.
- The `scope_class` column is preserved for backwards-compatible query-scope filtering via `bootInteractsWithAzScopes`.
- Entity-scoped permissions cache is cleared automatically on `assignScopedRole()` and `removeScopedRole()`.
