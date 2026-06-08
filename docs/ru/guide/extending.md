# Расширение

## Кастомный PermissionChecker

```php
use AzGuard\Contracts\PermissionCheckerInterface;

class TenantPermissionChecker implements PermissionCheckerInterface
{
    public function check(Authenticatable $user, PermissionInterface $permission): bool
    {
        // Кастомная логика: например, учитываем tenant
        $tenantId = app(TenantContext::class)->getCurrentTenantId();

        return DB::table('azguard_user_roles')
            ->where('user_id', $user->getAuthIdentifier())
            ->where('tenant_id', $tenantId)
            ->exists();
    }
}

// Регистрация в ServiceProvider
$this->app->bind(PermissionCheckerInterface::class, TenantPermissionChecker::class);
```

## Кастомный RoleResolver

```php
use AzGuard\Contracts\RoleResolverInterface;

class CachedRoleResolver implements RoleResolverInterface
{
    public function resolve(Authenticatable $user): array
    {
        return Cache::remember(
            "azguard_roles_{$user->getAuthIdentifier()}",
            300,
            fn () => $user->azguardRoles()->get()->toArray()
        );
    }
}
```

## Расширение трейта

```php
trait HasCustomAzGuard
{
    use HasAzGuard;

    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) return true;
        }
        return false;
    }

    public function canAccessPanel(string $panel): bool
    {
        return $this->azguardRoles()
            ->where('panel', $panel)
            ->exists();
    }
}
```
