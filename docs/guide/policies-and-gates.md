# Policies & Gates

AzGuard sits on top of Laravel's native Gate. You write standard Laravel policies; AzGuard auto-registers them and adds the panel-aware permission layer on top.

## How registration works

When the panel provider boots:

1. It scans `**/Policies/**/*Policy.php` under your Guards directory.
2. `PolicyAttributeRegistrar` reads `#[GateAbility]` attributes on policy methods.
3. For each method, it calls `Gate::define(resolvedAbility, [Policy::class, 'method'])`.
4. If `#[AzGuardPolicy(model: Foo::class)]` is present (or autodiscovered), it also calls `Gate::policy($model, $policy)`.

## Writing a policy

```php
namespace App\Guards\App\Documents\Policies;

use AzGuard\Attributes\GateAbility;
use AzGuard\Attributes\AzGuardPolicy;
use App\Models\Documents\Document;
use App\Guards\App\AppGuard;
use App\Guards\App\Permissions\DocumentsPermission;

#[AzGuardPolicy(model: Document::class)]
class DocumentsPolicy
{
    #[GateAbility(permission: DocumentsPermission::View)]
    public function canView(User $user, Document $document): bool
    {
        return $user->hasPermission(AppGuard::permission(DocumentsPermission::View))
            && $user->id === $document->owner_id;
    }

    #[GateAbility(permission: DocumentsPermission::Edit)]
    public function canEdit(User $user, Document $document): bool
    {
        return $user->hasPermission(AppGuard::permission(DocumentsPermission::Edit))
            && ! $document->isLocked();
    }

    #[GateAbility(permission: DocumentsPermission::Delete)]
    public function canDelete(User $user, Document $document): bool
    {
        return $user->hasPermission(AppGuard::permission(DocumentsPermission::Delete));
    }
}
```

## Gate::before — wildcards only

`Gate::before` is registered by AzGuard to short-circuit for super-admin roles. A role whose `permissions()` returns `['*']` causes `Gate::before` to return `true` **without calling the policy**.

No other logic runs in `Gate::before`. All real permission checks happen in policy methods via `hasPermission()`.

## Calling the Gate

Always pass the **enum case** directly — not a raw string. Laravel Gate accepts `BackedEnum` and calls `->value` automatically:

```php
// ✅ In a controller — enum case
Gate::allows(DocumentsPermission::View, $document);
Gate::authorize(DocumentsPermission::Edit, $document);
```

```blade
{{-- ✅ Option 1: pre-resolved boolean from controller (preferred) --}}
@if($can['delete'])
    <button>Delete</button>
@endif

{{-- ✅ Option 2: FQCN with ->value --}}
@can(\App\Guards\App\Permissions\DocumentsPermission::Delete->value, $document)
    <button>Delete</button>
@endcan

{{-- ✅ Shorthand — panel resolved from middleware context --}}
@azcan('documents.view')
    <a href="...">View</a>
@endazcan
```

::: tip Enum vs string
Enum cases are type-safe and refactor-safe. `@azcan` accepts the short key without the panel prefix — use it for panel-scoped templates. In PHP code (controllers, services, jobs), always pass the enum directly to `Gate::allows()` / `Gate::authorize()`.
:::
