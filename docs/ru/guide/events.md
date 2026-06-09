# События

AzGuard генерирует события Laravel, на которые можно подписаться через стандартный `EventServiceProvider`.

## Список событий

| Событие | Когда |
|---|---|
| `RoleAssigned` | После `assignRole()` |
| `RoleRevoked` | После `revokeRole()` |
| `PermissionGranted` | После `grantPermission()` |
| `PermissionRevoked` | После `revokeGrantedPermission()` |
| `PermissionDenied` | При отказе в доступе через middleware |

## Подписка

```php
// app/Providers/EventServiceProvider.php
use AzGuard\Events\{RoleAssigned, RoleRevoked, PermissionGranted, PermissionDenied};

protected $listen = [
    RoleAssigned::class => [
        App\Listeners\LogRoleAssignment::class,
        App\Listeners\NotifySecurityTeam::class,
    ],
    PermissionDenied::class => [
        App\Listeners\LogAccessDenied::class,
    ],
];
```

## Пример слушателя

```php
// app/Listeners/LogRoleAssignment.php
use AzGuard\Events\RoleAssigned;

class LogRoleAssignment
{
    public function handle(RoleAssigned $event): void
    {
        Log::info('Роль назначена', [
            'user_id' => $event->user->id,
            'role'    => $event->roleClass,
            'by'      => auth()->id(),
        ]);

        // Сброс кеша
        AzGuard::clearCache($event->user);
    }
}
```

## Аудит через события

```php
class AuditPermissionChanges
{
    public function handle(RoleAssigned|RoleRevoked $event): void
    {
        AuditLog::create([
            'action'     => class_basename($event),
            'user_id'    => $event->user->id,
            'role'       => $event->roleClass,
            'ip'         => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
```
