# Events

AzGuard dispatches Laravel events at key points in the permission lifecycle. You can listen to these events for auditing, cache busting, webhooks, or any custom logic.

## Available events

| Event class | When fired |
|---|---|
| `RoleAssigned` | After `$user->assignRole(...)` succeeds |
| `RoleRemoved` | After `$user->removeRole(...)` succeeds |
| `RolesSynced` | After `$user->syncRoles(...)` completes |
| `DirectGrantCreated` | After a direct grant is created |
| `DirectGrantRevoked` | After a direct grant is revoked (soft-deleted) |
| `DirectGrantExpired` | When the cache resolver encounters an expired grant |
| `PermissionCacheFlushed` | After `$user->flushPermissions()` is called |

All event classes live in the `AzGuard\Events` namespace.

## Listening to events

### In EventServiceProvider

```php
// app/Providers/EventServiceProvider.php
use AzGuard\Events\RoleAssigned;
use AzGuard\Events\DirectGrantCreated;

protected $listen = [
    RoleAssigned::class => [
        App\Listeners\AuditRoleChange::class,
    ],
    DirectGrantCreated::class => [
        App\Listeners\NotifyAdminOfGrant::class,
    ],
];
```

### Via closures

```php
// app/Providers/AppServiceProvider.php
use AzGuard\Events\RoleAssigned;

public function boot(): void
{
    Event::listen(RoleAssigned::class, function (RoleAssigned $event) {
        Log::info('Role assigned', [
            'user_id'  => $event->user->getKey(),
            'role'     => $event->role,
            'panel'    => $event->panel,
        ]);
    });
}
```

## Event payloads

### `RoleAssigned`

```php
public readonly Authenticatable $user;
public readonly string $role;   // role name, e.g. 'editor'
public readonly string $panel;  // panel id, e.g. 'app'
```

### `RoleRemoved`

```php
public readonly Authenticatable $user;
public readonly string $role;
public readonly string $panel;
```

### `RolesSynced`

```php
public readonly Authenticatable $user;
public readonly array  $added;    // role names added
public readonly array  $removed;  // role names removed
public readonly string $panel;
```

### `DirectGrantCreated`

```php
public readonly Authenticatable $user;
public readonly string          $permissionKey;  // e.g. 'app.documents.view'
public readonly string          $panel;
public readonly ?Carbon         $expiresAt;      // null = permanent
```

### `DirectGrantRevoked`

```php
public readonly Authenticatable $user;
public readonly string          $permissionKey;
public readonly string          $panel;
```

### `PermissionCacheFlushed`

```php
public readonly Authenticatable $user;
```

## Audit log example

A full audit listener pattern:

```php
// app/Listeners/AuditRoleChange.php
namespace App\Listeners;

use AzGuard\Events\RoleAssigned;
use AzGuard\Events\RoleRemoved;
use App\Models\AuditLog;

class AuditRoleChange
{
    public function handleAssigned(RoleAssigned $event): void
    {
        AuditLog::create([
            'event'    => 'role.assigned',
            'user_id'  => $event->user->getKey(),
            'metadata' => [
                'role'  => $event->role,
                'panel' => $event->panel,
            ],
        ]);
    }

    public function handleRemoved(RoleRemoved $event): void
    {
        AuditLog::create([
            'event'    => 'role.removed',
            'user_id'  => $event->user->getKey(),
            'metadata' => [
                'role'  => $event->role,
                'panel' => $event->panel,
            ],
        ]);
    }
}
```

## Disabling events

To suppress events in tests or bulk operations:

```php
// Temporarily disable all AzGuard events
AzGuard::withoutEvents(function () {
    User::all()->each->assignRole('viewer');
});
```

This uses the same pattern as `Model::withoutEvents()` and is safe to nest.
