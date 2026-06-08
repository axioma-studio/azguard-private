# Контекст (опционально)

Контекст позволяет привязать проверку прав к конкретной сущности — тенанту, команде, проекту.

## Установка контекста

```php
use AzGuard\Facades\AzGuard;

// Установить глобальный контекст для текущего запроса
AzGuard::setContext(['tenant_id' => $tenant->id]);

// Через middleware
class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        AzGuard::setContext([
            'tenant_id' => $request->user()->tenant_id,
        ]);
        return $next($request);
    }
}
```

## Проверка с контекстом

```php
// hasPermission учитывает текущий контекст автоматически
$user->hasPermission(ProjectPermission::Edit); // проверяет с tenant_id из контекста

// Явная передача контекста
$user->hasPermission(ProjectPermission::Edit, context: ['project_id' => 42]);

// Грант с контекстом
AzGuard::grant($user, ProjectPermission::Edit, context: ['project_id' => 42]);
```

## Контекстные роли

```php
class ProjectManagerRole implements RoleInterface
{
    public function permissions(): array
    {
        return [
            ProjectPermission::View,
            ProjectPermission::Edit,
            ProjectPermission::ManageMembers,
        ];
    }

    // Роль действует только в контексте проекта
    public function contextKey(): string
    {
        return 'project_id';
    }
}
```

::: tip
Контекст не влияет на производительность, когда не используется — нулевой оверхед по умолчанию.
:::
