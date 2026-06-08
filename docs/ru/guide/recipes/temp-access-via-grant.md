# Временный доступ через грант

## Сценарий: доступ на время отпуска коллеги

```php
// Передать права на время отсутствия
AzGuard::grant(
    user: $deputyUser,
    permission: FinancePermission::ApproveInvoices,
    expiresAt: $colleague->returnDate,
);

// Уведомление
Mail::to($deputyUser)->send(new TemporaryAccessGranted(
    permission: FinancePermission::ApproveInvoices,
    expiresAt: $colleague->returnDate,
));
```

## Сценарий: бета-функция для группы пользователей

```php
$betaUsers = User::where('beta_tester', true)->get();

$betaUsers->each(function ($user) {
    AzGuard::grant(
        $user,
        AnalyticsPermission::BetaDashboard,
        ttl: 30 * 24 * 3600 // 30 дней
    );
});
```

## Отслеживание активных грантов

```php
// Все активные временные гранты
$activeGrants = DirectGrant::active()
    ->with('user')
    ->where('permission', FinancePermission::ApproveInvoices->fullKey())
    ->get();
```
