# Контекстные права (azguard/context)

Пакет `azguard/context` — opt-in расширение для multi-workspace / multi-site сценариев.
Каждый пользователь может иметь **разные права в разных контекстах** (workspace, project, organisation и т.д.)
на одной и той же панели.

## Установка

```bash
composer require axioma-studio/azguard-context
php artisan vendor:publish --tag=azguard-context-migrations
php artisan migrate
```

## Концепции

| Термин | Описание |
|---|---|
| **AuthorizationContext** | Value object: `panelId` + `contextType` + `contextId` |
| **AuthorizationContextManager** | Singleton: хранит активный контекст per-panel на время request |
| **ResolvesContext** | Интерфейс resolver-а — извлекает контекст из `Request` |
| **ContextMergeStrategy** | Стратегия объединения глобальных и контекстных прав |
| **ContextualRoleGrantSource** | `GrantSource` с приоритетом 95, читает таблицу `az_guard_context_roles` |

## Быстрый старт

### 1. Создайте resolver

```php
use AzGuard\Context\Contracts\ResolvesContext;
use AzGuard\Context\AuthorizationContext;
use Illuminate\Http\Request;

final class WorkspaceContextResolver implements ResolvesContext
{
    public function resolve(Request $request): ?AuthorizationContext
    {
        $id = $request->route('workspace');

        return $id
            ? new AuthorizationContext('app', 'workspace', $id)
            : null;
    }

    public function panel(): string
    {
        return 'app';
    }
}
```

### 2. Зарегистрируйте в конфиге

```php
// config/az-guard-context.php
'resolvers' => [
    App\AzGuard\WorkspaceContextResolver::class,
],
```

### 3. Подключите middleware

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $m) {
    $m->alias(['azguard.context' => \AzGuard\Context\Middleware\SetAuthorizationContext::class]);
})
```

```php
// routes/web.php
Route::middleware(['auth', 'azguard.context'])
    ->group(function () {
        Route::get('/workspaces/{workspace}/posts', PostController::class);
    });
```

С этого момента `$user->hasAzPermission('app.posts.edit')` автоматически
учитывает права пользователя в текущем workspace.

## Проверка прав

### Глобальная (без контекста)

```php
$user->hasAzPermission('app.posts.edit');
```

### Одноразовая контекстная проверка

Не меняет глобальный `AuthorizationContextManager`:

```php
// Через удобный alias
$user->hasAzPermissionIn('workspace', $workspaceId, 'app.posts.edit');

// Через основной метод с duck-typed объектом
$user->hasAzPermission('app.posts.edit', 'app', (object) [
    'contextType' => 'workspace',
    'contextId'   => $workspaceId,
]);
```

### Тихая версия (для Blade / UI)

```php
$user->checkAzPermission('app.posts.edit', 'app', (object) [
    'contextType' => 'workspace',
    'contextId'   => $workspaceId,
]);
```

### Blade-директива

```blade
@azcan('app.posts.edit')
    {{-- права из текущего контекста (если middleware установлен) --}}
@endazcan
```

## Выдача контекстных прав

Права хранятся в таблице `az_guard_context_roles`.

Заполняйте вручную или через свой сервис:

```php
use Illuminate\Support\Facades\DB;

DB::table('az_guard_context_roles')->insert([
    'model_type'     => get_class($user),
    'model_id'       => $user->id,
    'context_type'   => 'workspace',
    'context_id'     => (string) $workspaceId,
    'panel_id'       => 'app',
    'permission_key' => 'app.posts.edit',
    'created_at'     => now(),
    'updated_at'     => now(),
]);
```

Wildcard (`*`) — полный доступ в контексте:

```php
DB::table('az_guard_context_roles')->insert([
    ...
    'permission_key' => '*',
]);
```

## Стратегии объединения прав

Настраивается в `config/az-guard-context.php`:

```php
'merge_strategy' => \AzGuard\Context\Strategies\GlobalPlusContextStrategy::class,
```

| Класс | Поведение |
|---|---|
| `GlobalPlusContextStrategy` | global ∪ context **(дефолт)** |
| `ContextOnlyStrategy` | только context, global игнорируется |
| `DenyWithoutContextStrategy` | пустой set без контекста; с контекстом — global ∪ context |

Можно реализовать свою стратегию:

```php
use AzGuard\Context\Contracts\ContextMergeStrategy;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;

final class MyStrategy implements ContextMergeStrategy
{
    public function merge(
        Authenticatable $user,
        string $panelId,
        PermissionSet $global,
        ?PermissionSet $context,
    ): PermissionSet {
        // ваша логика
    }
}
```

## Приоритеты GrantSource

| Source | Приоритет |
|---|---|
| ClassRoleGrantSource | 100 |
| **ContextualRoleGrantSource** | **95** |
| DatabaseRoleGrantSource | 90 |
| DirectGrantSource | 80 |

Все источники объединяются в `EffectivePermissionResolver` — контекстные права
не «перебивают» class role, а дополняют набор.

## Обратная совместимость

- Пакет **opt-in**: если не установлен, `HasAzGuard` работает идентично предыдущей версии.
- `hasAzPermissionIn()` возвращает `false` если пакет не установлен.
- `hasAzPermission(..., $context)` делает fallback к глобальной проверке если пакет не установлен.
