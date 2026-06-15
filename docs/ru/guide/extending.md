# Расширение

## Кастомный GrantSource

```php
use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;

class TenantGrantSource implements GrantSource
{
    public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet
    {
        // Кастомная логика: например, учитываем tenant
        $tenantId = app(TenantContext::class)->getCurrentTenantId();

        $keys = DB::table('tenant_permissions')
            ->where('user_id', $user->getAuthIdentifier())
            ->where('tenant_id', $tenantId)
            ->pluck('permission_key')
            ->all();

        return PermissionSet::of($keys);
    }

    public function priority(): int
    {
        return 85;
    }
}

// Регистрация в register() сервис-провайдера
use AzGuard\Facades\AzGuard;

public function register(): void
{
    AzGuard::registerGrantSource(TenantGrantSource::class);
}
```

## Кастомная стратегия слияния (Context)

```php
use AzGuard\Context\Contracts\MergeStrategy;
use AzGuard\Registry\Values\PermissionSet;

class CustomMergeStrategy implements MergeStrategy
{
    public function merge(PermissionSet $global, ?PermissionSet $context): PermissionSet
    {
        // Кастомная логика объединения глобальных и контекстных прав
        return $context ?? $global;
    }
}
```

Подключается через `config/az-guard-context.php`:

```php
'merge_strategy' => App\AzGuard\CustomMergeStrategy::class,
```

## Расширение трейта

```php
use AzGuard\Concerns\HasAzGuard;

trait HasCustomAzGuard
{
    use HasAzGuard;

    public function hasEveryPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }
}
```
