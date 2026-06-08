# Multi-Tenant роли

## Контекстные роли по тенанту

```php
// Назначить роль в контексте конкретного тенанта
$user->assignRole(EditorRole::class, context: ['tenant_id' => $tenant->id]);

// Проверка
AzGuard::setContext(['tenant_id' => $tenant->id]);
$user->hasPermission(PostsPermission::Edit); // true только для этого тенанта
```

## Middleware для установки тенанта

```php
class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Tenant::fromSubdomain($request->getHost());
        AzGuard::setContext(['tenant_id' => $tenant->id]);
        return $next($request);
    }
}
```

## Изоляция через панели

Альтернатива: создать отдельную панель для каждого тенанта (только для небольшого числа тенантов):

```php
// config/azguard.php
'panels' => [
    'tenant_1' => App\AzGuard\Tenant1\TenantPanel::class,
    'tenant_2' => App\AzGuard\Tenant2\TenantPanel::class,
],
```
