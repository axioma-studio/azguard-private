# Permissions

A permission is a **backed string enum** that implements `PermissionInterface`. Enum cases are your single source of truth — no database rows, no string literals scattered across the codebase.

## Naming convention

```
{domain}.{action}           →  documents.view
{domain}.{sub}.{action}     →  documents.versions.create
{domain}.workflow.{action}  →  documents.workflow.publish
{domain}.view               →  dashboard.view   (UI section)
```

Keep names lowercase, dot-separated, no panel prefix — AzGuard resolves the panel prefix at runtime.

## Creating a permission enum

```bash
php artisan azguard:make-permission App DocumentsPermission
```

```php
namespace App\Guards\App\Permissions;

use AzGuard\Contracts\PermissionInterface;
use AzGuard\Attributes\GateAbility;
use AzGuard\Attributes\RoleOnly;

enum DocumentsPermission: string implements PermissionInterface
{
    // Registered with Gate — policy method called on Gate::allows()
    #[GateAbility]
    case View   = 'documents.view';

    #[GateAbility]
    case Create = 'documents.create';

    #[GateAbility]
    case Edit   = 'documents.edit';

    #[GateAbility]
    case Delete = 'documents.delete';

    // Checked only via hasAzPermission() — no Gate policy needed
    #[RoleOnly]
    case Export = 'documents.export';
}
```

## Resolved permissions

The resolved form is `{panel}.{case-value}`. Use `AppGuard::permission()` inside role definitions to get it:

```php
AppGuard::permission(DocumentsPermission::View)
// returns: "app.documents.view"
```

Never hardcode the resolved string in roles — always go through the guard helper.

## Permission table

| Type | Raw value | Resolved (panel=app) |
|---|---|---|
| CRUD | `documents.view` | `app.documents.view` |
| Nested action | `documents.versions.create` | `app.documents.versions.create` |
| Workflow | `documents.workflow.publish` | `app.documents.workflow.publish` |
| UI section | `dashboard.view` | `app.dashboard.view` |
| Role-only | `documents.export` | `app.documents.export` |

## `#[RoleOnly]` — no Gate policy

Permissions marked `#[RoleOnly]` are **not** registered with Gate. They are checked exclusively via `$user->hasAzPermission()`. Use for non-model checks (dashboard access, feature flags, UI sections) where a full policy would be overkill.

## TypeScript export

If your front-end is TypeScript, add `#[TypeScript]` to the enum in your application. The enum values become a TypeScript union type via [`typescript-transformer`](https://github.com/spatie/laravel-typescript-transformer):

```typescript
type DocumentsPermission = 'documents.view' | 'documents.create' | 'documents.edit' | 'documents.delete';
```

::: info
AzGuard ships only the PHP attribute. The TypeScript generation is handled by your application's transformer pipeline.
:::
