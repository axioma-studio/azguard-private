# Контекст (опциональный)

Контекст позволяет проверять права с учётом дополнительного условия — например, tenant, команды или проекта.

## Включение контекста

```php
// config/azguard.php
'context' => [
    'enabled'  => true,
    'resolver' => App\AzGuard\Context\TenantContextResolver::class,
],
```

## Resolver

```php
use AzGuard\Contracts\ContextResolverInterface;

class TenantContextResolver implements ContextResolverInterface
{
    public function resolve(Request $request): ?string
    {
        // Возвращаем строковый ключ контекста или null
        return $request->header('X-Tenant-ID')
            ?? auth()->user()?->tenant_id;
    }
}
```

## Использование

```php
// AzGuard автоматически добавляет контекст к каждой проверке
$user->hasPermission(ProjectsPermission::Edit); // учитывает текущий tenant

// Явное указание контекста
AzGuard::withContext('tenant-42')
    ->hasPermission($user, ProjectsPermission::Edit);

// Без контекста
AzGuard::withoutContext()
    ->hasPermission($user, ProjectsPermission::Edit);
```

## Назначение ролей с контекстом

```php
// Роль только в рамках конкретного tenant
$user->assignRole(EditorRole::class, context: 'tenant-42');

// Проверка с тем же контекстом
AzGuard::withContext('tenant-42')
    ->hasPermission($user, PostsPermission::Edit); // true

AzGuard::withContext('tenant-99')
    ->hasPermission($user, PostsPermission::Edit); // false
```
