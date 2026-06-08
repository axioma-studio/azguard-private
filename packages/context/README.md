# azguard/context

Опциональный пакет для multi-workspace / multi-site поддержки в AzGuard.

## Установка

```bash
composer require axioma-studio/azguard-context
php artisan vendor:publish --tag=azguard-context-config
```

## Концепция

`azguard/context` добавляет понятие **контекста авторизации** — пространства (workspace, site, tenant), в рамках которого проверяются права пользователя.

Пакет полностью opt-in: без его установки поведение `azguard/core` не меняется.

## Быстрый старт

```php
// 1. Реализуйте ResolvesContext в приложении
class WorkspaceContextResolver implements ResolvesContext
{
    public function resolve(Request $request): ?AuthorizationContextInterface
    {
        $id = $request->route('workspace');
        return $id ? AuthorizationContext::make('workspace', (string) $id) : null;
    }
}

// 2. Зарегистрируйте в config/az-guard-context.php
'context_resolver' => WorkspaceContextResolver::class,

// 3. Добавьте middleware на маршруты
Route::middleware(['auth', 'azguard.context'])->group(function () {
    Route::get('/workspaces/{workspace}/posts', PostController::class);
});

// 4. Права проверяются как обычно
Gate::allows('app.posts.publish'); // учитывает контекст workspace автоматически
```

## Стратегии merge

| Стратегия | Поведение |
|---|---|
| `GlobalPlusContextStrategy` | глобальные ∪ контекстные (default) |
| `ContextOnlyStrategy` | только контекстные, глобальные игнорируются |
| `DenyWithoutContextStrategy` | exception если контекст не установлен |

См. полную документацию: `docs/guide/context.md` (Sprint 8).
