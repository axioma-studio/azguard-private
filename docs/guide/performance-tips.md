# Performance Tips

AzGuard resolves permissions at runtime from in-memory role class definitions, which is fast by default. The following tips help you squeeze out every millisecond in high-traffic applications.

## Enable the permission cache

AzGuard ships with an optional Redis/database cache for resolved permission sets. Enable it in `config/azguard.php`:

```php
'cache' => [
    'enabled' => true,
    'store'   => 'redis',        // any configured cache store
    'ttl'     => 3600,           // seconds
],
```

See [Cache](./cache.md) for the full reference, including how to invalidate on role changes.

## Avoid N+1 checks in loops

If you are checking permissions inside a loop over a collection, load roles eagerly and pass a preloaded user:

```php
// ❌ Bad — triggers a DB query per user
$users->each(fn ($user) => $user->hasPermission(DocumentsPermission::View));

// ✅ Good — load roles once
$users->load('roles');
$users->each(fn ($user) => $user->hasPermission(DocumentsPermission::View));
```

## Use `hasPermission` over Gate::allows in tight loops

`Gate::allows()` runs the full Gate pipeline including all registered `before` and `after` callbacks. Inside a tight loop, calling `$user->hasPermission()` directly is cheaper:

```php
// Faster in loops
$user->hasPermission(DocumentsPermission::View);

// Full Gate pipeline — fine for HTTP request boundaries
Gate::allows('app.documents.view');
```

## Permission catalog warm-up

If you use the [Permission Catalog](./permission-catalog.md), warm it up during deployment rather than on the first request:

```bash
php artisan azguard:cache:warm
```

Add this to your `php artisan deploy` or Forge/Envoyer deploy hook.

## Octane users: no extra configuration needed

AzGuard resolves roles per-request with no static state. Under Octane the memory footprint stays flat regardless of concurrency.
