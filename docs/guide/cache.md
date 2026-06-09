# Cache

AzGuard caches resolved permission sets to avoid repeated database queries within a single request. The cache is **request-scoped by default** — making it safe for Octane and Kubernetes without any configuration.

## How caching works

```
Request comes in
  → User authenticated
  → First hasPermission() / Gate::allows() call
      → EffectivePermissionResolver queries all GrantSources (roles + direct grants)
      → Result stored in PermissionResolverCache (in-memory, request-scoped)
  → Subsequent checks on the same request
      → Cache hit — zero DB queries
```

The in-memory cache is **never shared** between requests. Under Octane, each worker resolves permissions independently per request.

## Manual cache flush

If you modify roles or grants within the same request (e.g., in a test or admin action), flush the cache to get fresh results:

```php
$user->assignRole('editor');
$user->flushPermissions();  // clears in-memory cache for this user

$user->hasPermission(DocumentsPermission::View);  // re-resolves from DB
```

## Cache in tests

Always call `flushPermissions()` between state changes in a single test:

```php
public function test_role_change_takes_effect(): void
{
    $user = User::factory()->create();
    $user->assignRole('viewer');

    $this->assertFalse($user->hasPermission(DocumentsPermission::Delete));

    // Upgrade role
    $user->syncRoles(['editor']);
    $user->flushPermissions();  // required — clears the in-memory cache

    $this->assertTrue($user->hasPermission(DocumentsPermission::Edit));
}
```

## Redis / persistent cache (optional)

For high-traffic applications, enable cross-request caching:

```php
// config/az-guard.php
'cache' => [
    'enabled' => true,
    'store'   => 'redis',   // any Laravel cache store
    'ttl'     => 300,       // seconds — 5 minutes is a safe default
],
```

When enabled, resolved `PermissionSet` objects are serialized into the cache store. The cache key includes the user ID, panel, and a version tag. The tag is invalidated automatically when `flushPermissions()` is called.

::: warning Always flush after changes
With persistent cache enabled, always call `$user->flushPermissions()` after any role or grant change that should take effect immediately. Without it, the old permission set will be served until the TTL expires.
:::

## Cache key structure

```
az_guard:{user_id}:{panel}:{version_tag}
```

The version tag is stored separately and bumped atomically on `flushPermissions()`, so stale keys expire naturally without explicit deletion.

## Redis strategy recommendations

| Scenario | Recommended TTL | Notes |
|---|---|---|
| Low-traffic app | In-memory only (`enabled: false`) | No overhead, simplest setup |
| Standard SaaS | 300s (5 min) | Good balance, minimal staleness |
| High-traffic read-heavy | 600–1800s | Accept slightly longer flush delay |
| Admin / security-critical | 60s or in-memory | Faster invalidation on role changes |

## Cache invalidation via events

Auto-invalidate the cache when a user's status changes:

```php
// app/Observers/UserObserver.php
class UserObserver
{
    public function updated(User $user): void
    {
        if ($user->isDirty('status') || $user->isDirty('is_active')) {
            $user->flushPermissions();
        }
    }
}
```

Or hook into AzGuard's own events:

```php
use AzGuard\Events\RoleAssigned;
use AzGuard\Events\RoleRevoked;
use AzGuard\Events\GrantGiven;

// AzGuard automatically flushes the cache on these events,
// but you can listen to chain additional side effects:
Event::listen(RoleAssigned::class, function (RoleAssigned $event): void {
    Log::info("Role [{$event->roleName}] assigned to user #{$event->user->id}");
});
```

## Octane notes

AzGuard's in-memory cache uses a plain PHP array scoped to the current request — no static properties or container singletons that survive between requests. It is safe to use with:

- Laravel Octane (Swoole, RoadRunner)
- Kubernetes with multiple replicas
- Queue workers (each job is its own request cycle)

::: tip
Disable persistent cache (`'enabled' => false`) in tests to avoid cross-test contamination. The in-memory cache is automatically cleared between test cases by `RefreshDatabase`.
:::

## Performance tips

- **Do not call `flushPermissions()` in middleware** unless necessary — it defeats the request cache and re-queries on every check.
- **Eager-load roles** when building user lists: `User::with('azRoles')->paginate()`. This prevents N+1 during the first permission resolution.
- **With persistent cache**, set a TTL that reflects how frequently roles change in your app. 5 minutes is a safe starting point.
