# Временный доступ через грант

Класический сценарий: дать пользователю доступ к функции на N часов.

## Реализация

```php
// Выдать доступ на 2 часа
app(GrantTemporaryAccess::class)->execute(
    user: $user,
    permission: ReportsPermission::Export,
    hours: 2
);

// Сервис
class GrantTemporaryAccess
{
    public function execute(User $user, PermissionInterface $permission, int $hours): void
    {
        $user->grantPermission(
            $permission,
            expiresAt: Carbon::now()->addHours($hours)
        );

        event(new TemporaryAccessGranted($user, $permission, $hours));
    }
}
```

## Уведомление пользователя

```php
// Listener
class NotifyUserOfTemporaryAccess
{
    public function handle(TemporaryAccessGranted $event): void
    {
        $event->user->notify(
            new TemporaryAccessNotification($event->permission, $event->hours)
        );
    }
}
```
