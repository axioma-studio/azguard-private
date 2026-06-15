# Контекст (опциональный)

Пакет `axioma-studio/azguard-context` позволяет проверять права с учётом
дополнительной сущности — например, workspace, команды или проекта.

```bash
composer require axioma-studio/azguard-context
```

## Конфигурация

```php
// config/az-guard-context.php
use AzGuard\Context\Strategies\GlobalPlusContextStrategy;

return [
    // Стратегия слияния глобальных и контекстных прав.
    'merge_strategy' => GlobalPlusContextStrategy::class,

    // Список FQCN-резолверов, реализующих ResolvesContext.
    'resolvers' => [
        App\AzGuard\WorkspaceContextResolver::class,
    ],
];
```

Встроенные стратегии: `GlobalPlusContextStrategy` (по умолчанию, `global ∪ context`),
`ContextOnlyStrategy` (только контекст), `DenyWithoutContextStrategy` (без контекста — пустой набор).

## Resolver

```php
use AzGuard\Context\AuthorizationContext;
use AzGuard\Context\Contracts\ResolvesContext;
use Illuminate\Http\Request;

class WorkspaceContextResolver implements ResolvesContext
{
    public function resolve(Request $request): ?AuthorizationContext
    {
        $id = $request->route('workspace');

        return $id
            ? new AuthorizationContext('app', 'workspace', $id)
            : null;
    }

    public function panelId(): string
    {
        return 'app';
    }
}
```

## Проверка в контексте

```php
// Разовая проверка в контексте без изменения глобального состояния
$user->hasPermissionIn('workspace', 42, ProjectsPermission::Edit);        // панель по умолчанию
$user->hasPermissionIn('workspace', 42, 'app.projects.edit', 'app');      // явная панель
```

## Scoped-роли (entity scopes)

Для привязки роли к конкретной сущности используйте трейт `HasScopedRoles`:

```php
use AzGuard\Concerns\HasAzGuard;
use AzGuard\Concerns\HasScopedRoles;

class User extends Authenticatable
{
    use HasAzGuard, HasScopedRoles;
}
```

```php
// Назначить роль в рамках конкретной сущности
$user->assignScopedRole('editor', $workspace);

// Проверить роль в этой сущности
$user->hasScopedRole('editor', $workspace);          // true

// Проверить право в этой сущности
$user->hasScopedPermission(PostsPermission::Edit, $workspace); // true
$user->hasScopedPermission(PostsPermission::Edit, $otherWorkspace); // false
```
