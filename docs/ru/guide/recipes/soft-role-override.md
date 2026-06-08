# Мягкое переопределение роли

Иногда нужно дать пользователю дополнительное право без изменения его роли — например, временный доступ к разделу.

## Решение: прямой грант

```php
// Дать право на 24 часа
AzGuard::grant($user, AdminPermission::ViewReports, ttl: 86400);

// Пользователь с ролью ViewerRole теперь может видеть отчёты
$user->hasPermission(AdminPermission::ViewReports); // true

// Через 24 часа — автоматически false
```

## Через событие

```php
// Временный доступ при активации trial-функции
Event::listen(TrialFeatureActivated::class, function ($event) {
    AzGuard::grant(
        $event->user,
        AnalyticsPermission::Advanced,
        ttl: $event->trialDays * 86400
    );
});
```
