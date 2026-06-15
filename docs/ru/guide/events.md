# События

AzGuard генерирует события Laravel, на которые можно подписаться через стандартный `EventServiceProvider`.

## Список событий

| Событие | Когда | Свойства |
|---|---|---|
| `RoleAttached` | После `assignRole()` / `syncRoles()` | `$model`, `$role` |
| `RoleDetached` | После `removeRole()` / `syncRoles()` | `$model`, `$role` |
| `GrantGiven` | После `grant()` | `$user`, `$permissionKey`, `$panelId`, `$grant` |
| `GrantRevoked` | После `revoke()` | `$user`, `$permissionKey`, `$panelId` |

::: tip
AzGuard **автоматически** сбрасывает кеш прав по этим событиям — регистрировать
слушатель для сброса кеша не нужно.
:::

## Подписка

```php
// app/Providers/EventServiceProvider.php
use AzGuard\Events\{RoleAttached, RoleDetached, GrantGiven, GrantRevoked};

protected $listen = [
    RoleAttached::class => [
        App\Listeners\LogRoleAssignment::class,
        App\Listeners\NotifySecurityTeam::class,
    ],
    GrantGiven::class => [
        App\Listeners\LogGrantGiven::class,
    ],
];
```

## Пример слушателя

```php
// app/Listeners/LogRoleAssignment.php
use AzGuard\Events\RoleAttached;

class LogRoleAssignment
{
    public function handle(RoleAttached $event): void
    {
        Log::info('Роль назначена', [
            'model_id' => $event->model->getKey(),
            'role'     => $event->role->name,
            'by'       => auth()->id(),
        ]);
    }
}
```

## Аудит через события

```php
use AzGuard\Events\{RoleAttached, RoleDetached};

class AuditRoleChanges
{
    public function handle(RoleAttached|RoleDetached $event): void
    {
        AuditLog::create([
            'action'     => class_basename($event),
            'model_id'   => $event->model->getKey(),
            'role'       => $event->role->name,
            'ip'         => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
```
