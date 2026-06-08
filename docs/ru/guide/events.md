# События

AzGuard диспатчит события Laravel при всех изменениях прав доступа.

## Список событий

| Событие | Когда |
|---|---|
| `RoleAssigned` | Роль назначена пользователю |
| `RoleRevoked` | Роль отозвана |
| `RolesSynced` | Роли синхронизированы (`syncRoles`) |
| `DirectGrantCreated` | Создан прямой грант |
| `DirectGrantRevoked` | Прямой грант отозван |
| `DirectGrantExpired` | Прямой грант истёк |

## Подписка

```php
// app/Providers/AppServiceProvider.php
use AzGuard\Events\RoleAssigned;
use AzGuard\Events\DirectGrantCreated;

public function boot(): void
{
    Event::listen(RoleAssigned::class, function (RoleAssigned $event) {
        Log::info('Роль назначена', [
            'user_id'    => $event->user->id,
            'role'       => $event->roleClass,
            'assigned_by'=> auth()->id(),
        ]);
    });

    Event::listen(DirectGrantCreated::class, function (DirectGrantCreated $event) {
        // Отправить уведомление пользователю
        $event->user->notify(new PermissionGrantedNotification($event->permission));
    });
}
```

## Аудит-лог

```php
class AzGuardAuditListener
{
    public function handleRoleAssigned(RoleAssigned $event): void
    {
        AuditLog::create([
            'action'     => 'role_assigned',
            'user_id'    => $event->user->id,
            'role'       => $event->roleClass,
            'actor_id'   => auth()->id(),
            'ip'         => request()->ip(),
        ]);
    }
}
```
