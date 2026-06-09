# Multi-Tenant роли

В SaaS-приложении пользователь может иметь разные роли в разных тенантах.

## Через контекст

```php
// Назначение роли в контексте тенанта
$user->assignRole(EditorRole::class, context: "tenant-{$tenant->id}");

// Проверка с тем же контекстом
AzGuard::withContext("tenant-{$tenant->id}")
    ->hasPermission($user, PostsPermission::Edit); // true

// В другом тенанте
AzGuard::withContext("tenant-999")
    ->hasPermission($user, PostsPermission::Edit); // false
```

## Middleware для тенантов

```php
// app/Http/Middleware/SetTenantContext.php
public function handle(Request $request, Closure $next): Response
{
    $tenantId = $request->header('X-Tenant-ID')
        ?? auth()->user()?->tenant_id;

    AzGuard::setContext("tenant-{$tenantId}");

    return $next($request);
}
```
