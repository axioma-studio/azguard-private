# Performance Tips

AzGuard resolves permissions at runtime from in-memory role class definitions, which is fast by default. The following tips help you squeeze out every millisecond in high-traffic applications.

## Enable the permission cache

AzGuard ships with an optional Redis/database cache for resolved permission sets. By
default `cache.store` is `'array'` (request-scoped). Point it at a persistent store in
`config/az-guard.php`:

```php
'cache' => [
    'store'           => 'redis',  // any configured cache store; 'array' = request-scoped
    'expiration_time' => 3600,     // seconds
    'key'             => 'az_guard',
],
```

See [Cache](/advanced/cache) for the full reference, including how to invalidate on role changes.

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

## Validate the catalog at deploy time

Validate the [Permission Catalog](/best-practices/permission-catalog) during deployment so
misconfigurations surface in CI rather than at runtime:

```bash
php artisan guard:catalog:validate
```

Add this to your deploy or Forge/Envoyer deploy hook.

## Octane users: no extra configuration needed

AzGuard resolves roles per-request with no static state. Under Octane the memory footprint stays flat regardless of concurrency.
