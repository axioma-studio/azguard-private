# Events

AzGuard dispatches Laravel events at key points in the permission lifecycle. You can listen to these events for auditing, cache busting, webhooks, or any custom logic.

## Available events

| Event class | When fired |
|---|---|
| `RoleAttached` | After a role is attached via `$user->assignRole(...)` / `syncRoles(...)` |
| `RoleDetached` | After a role is detached via `$user->removeRole(...)` / `syncRoles(...)` |
| `GrantGiven` | After a direct grant is created |
| `GrantRevoked` | After a direct grant is revoked |

All event classes live in the `AzGuard\Events` namespace. AzGuard **automatically
flushes the permission cache** on these events — you do not need a flush listener.

## Listening to events

### In EventServiceProvider

```php
// app/Providers/EventServiceProvider.php
use AzGuard\Events\RoleAttached;
use AzGuard\Events\GrantGiven;

protected $listen = [
    RoleAttached::class => [
        App\Listeners\AuditRoleChange::class,
    ],
    GrantGiven::class => [
        App\Listeners\NotifyAdminOfGrant::class,
    ],
];
```

### Via closures

```php
// app/Providers/AppServiceProvider.php
use AzGuard\Events\RoleAttached;

public function boot(): void
{
    Event::listen(RoleAttached::class, function (RoleAttached $event) {
        Log::info('Role assigned', [
            'model_id' => $event->model->getKey(),
            'role'     => $event->role->name,
        ]);
    });
}
```

## Event payloads

### `RoleAttached`

```php
public Model $model;  // the model the role was attached to
public Role  $role;   // the AzGuard\Models\Role instance
```

### `RoleDetached`

```php
public Model $model;
public Role  $role;
```

### `GrantGiven`

```php
public Authenticatable $user;
public string          $permissionKey;  // e.g. 'app.documents.view'
public string          $panelId;        // panel id, e.g. 'app'
public DirectGrant     $grant;          // the AzGuard\Models\DirectGrant
```

### `GrantRevoked`

```php
public Authenticatable $user;
public string          $permissionKey;
public string          $panelId;
```

## Audit log example

A full audit listener pattern:

```php
// app/Listeners/AuditRoleChange.php
namespace App\Listeners;

use AzGuard\Events\RoleAttached;
use AzGuard\Events\RoleDetached;
use App\Models\AuditLog;

class AuditRoleChange
{
    public function handleAttached(RoleAttached $event): void
    {
        AuditLog::create([
            'event'    => 'role.attached',
            'user_id'  => $event->model->getKey(),
            'metadata' => [
                'role' => $event->role->name,
            ],
        ]);
    }

    public function handleDetached(RoleDetached $event): void
    {
        AuditLog::create([
            'event'    => 'role.detached',
            'user_id'  => $event->model->getKey(),
            'metadata' => [
                'role' => $event->role->name,
            ],
        ]);
    }
}
```

## Suppressing events in tests

AzGuard dispatches plain Laravel events, so use the framework's own `Event::fake()`
to suppress (and assert on) them in tests or bulk operations:

```php
use Illuminate\Support\Facades\Event;
use AzGuard\Events\RoleAttached;

Event::fake([RoleAttached::class]);

User::all()->each->assignRole('viewer');

Event::assertDispatched(RoleAttached::class);
```
