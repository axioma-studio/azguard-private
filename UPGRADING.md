# Upgrading

## 0.1 → 0.2

An integration-polish release. The changes are breaking but mechanical. All three
packages move in lockstep — upgrade `axioma-studio/azguard-core`,
`-context` and `-filament` together to `^0.2`.

### 1. Adopt the public actor contract (recommended)

You can now type-hint a real interface instead of relying on the traits. Declare
it on your User model — the `HasAzGuard` trait already provides every method, so
no new code is needed:

```php
use AzGuard\Contracts\AzGuardUser;
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable implements AzGuardUser
{
    use HasAzGuard;
}
```

Opt into the scoped-role / direct-grant contracts alongside their traits. The
contract and trait share a short name (like Laravel's own `Authorizable`), so
alias the contract:

```php
use AzGuard\Contracts\HasScopedRoles as HasScopedRolesContract;
use AzGuard\Concerns\HasScopedRoles;

class User extends Authenticatable implements AzGuardUser, HasScopedRolesContract
{
    use HasAzGuard;
    use HasScopedRoles;
}
```

This is optional — existing models keep working — but it makes your own services
type-safe.

### 2. `hasGrant()` now resolves the default panel

`HasDirectGrants::hasGrant($permission)` (no `$panelId`) previously matched a raw
key across **any** panel. It now resolves the default panel (`az-guard.default_panel`,
else `'app'`) and scopes the query to it, like `hasPermission()`.

- If you always passed a panel — no change.
- If you relied on the old cross-panel behavior, pass the panel explicitly, or set
  `az-guard.default_panel`.

### 3. `morphType` fails loud

An invalid `AZ_GUARD_MORPH_TYPE` / `az-guard.column_names.morph_type` (anything
other than `int`, `ulid`, `uuid`) now throws `InvalidMorphTypeException` at boot
instead of silently using `int`. Fix the value if your boot starts failing — that
means it was misconfigured before.

### 4. Prefer `PermissionKey::WILDCARD` over `'*'`

The wildcard value is unchanged (`'*'`), so hardcoded literals still work. For
forward-compatibility, reference `AzGuard\PermissionKey::WILDCARD` (or
`PermissionSet::WILDCARD`) instead.

### 5. Custom `AzGuardManagerInterface` implementations

If you reimplemented `AzGuardManagerInterface` (uncommon), add the new methods:
`isSuperAdmin()`, `hasContextGuard()`, `panelIdForPermission()`.

### 6. Super-admin via `isSuperAdmin()` + `Gate::before`

Instead of inferring super-admin from `hasPermission('*')`, use the first-class
check and wire absolute-allow in one place:

```php
use AzGuard\Contracts\AzGuardUser;
use Illuminate\Support\Facades\Gate;

Gate::before(fn ($user, string $ability) =>
    $user instanceof AzGuardUser && $user->isSuperAdmin() ? true : null);
```
