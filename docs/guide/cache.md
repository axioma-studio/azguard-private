# Cache

AzGuard caches resolved permission sets to avoid repeated database queries within a single request.

## How caching works

Permissions are resolved once per user per panel per request and stored in memory:

```
Request comes in
  → User authenticated
  → First hasPermission() call
      → EffectivePermissionResolver queries all GrantSources (roles + direct grants)
      → Result stored in PermissionResolverCache (in-memory, request-scoped)
  → Subsequent hasPermission() calls on same request
      → Cache hit — no DB query
```

The cache is **request-scoped** — it is never shared between requests. This makes AzGuard safe for Laravel Octane without any additional configuration.

## Manual cache flush

If you modify roles or grants within the same request (e.g., in a test or admin action), flush the cache to get fresh results:

```php
$user->assignRole('editor');
$user->flushPermissions();  // clears in-memory cache

$user->hasPermission(DocumentsPermission::View);  // re-resolves from DB
```

## Cache in tests

In test suites that modify permissions within a single test, always call `flushPermissions()` between state changes:

```php
public function test_role_change_takes_effect(): void
{
    $user = User::factory()->create();
    $user->assignRole('viewer');

    $this->assertFalse($user->hasPermission(DocumentsPermission::Delete));

    // Upgrade role
    $user->syncRoles(['editor']);
    $user->flushPermissions();  // <-- required

    $this->assertTrue($user->hasPermission(DocumentsPermission::Edit));
}
```

## Redis / persistent cache (optional)

For high-traffic applications, you can enable cross-request caching with a Laravel cache store:

```php
// config/az-guard.php
'cache' => [
    'enabled' => true,
    'store'   => 'redis',   // any Laravel cache store
    'ttl'     => 300,       // seconds
],
```

When enabled, resolved `PermissionSet` objects are serialized and stored in the cache store. The cache key includes the user ID, panel, and a version tag that is invalidated when you call `flushPermissions()`.

::: warning
When using persistent cache, always call `$user->flushPermissions()` after any role or grant change that should take effect immediately.
:::

## Cache invalidation in events

You can hook into Eloquent events to auto-invalidate the cache:

```php
// app/Observers/UserObserver.php
class UserObserver
{
    public function updated(User $user): void
    {
        if ($user->isDirty('status')) {
            $user->flushPermissions();
        }
    }
}
```

## Performance tips

- **Do not call `flushPermissions()` in middleware** unless necessary — it defeats the request cache.
- **With persistent cache enabled**, set a TTL that matches how frequently your roles change. 5 minutes (300s) is a safe default.
- **Disable persistent cache in tests** — the in-memory cache is faster and automatically isolated per test.
