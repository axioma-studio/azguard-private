# Супер-администратор

Супер-админ — пользователь, который **автоматически имеет все права** без явного перечисления.

## Настройка

### Через роль (c SuperAdminInterface)

```php
// app/AzGuard/App/Roles/SuperAdminRole.php
use AzGuard\Contracts\RoleInterface;
use AzGuard\Contracts\SuperAdminInterface;

class SuperAdminRole implements RoleInterface, SuperAdminInterface
{
    public function permissions(): array
    {
        return [];  // игнорируется: super-admin проходит любую проверку
    }
}
```

### Через Gate::before()

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::before(function ($user, $ability) {
        if ($user->hasRole(SuperAdminRole::class)) {
            return true;  // пропустить любой Gate-чек
        }
    });
}
```

::: warning
Используйте `SuperAdminInterface`, если хотите чтобы AzGuard-события и `azguard:doctor` распознавали роль супер-админа.
:::

## Назначение

```php
$user->assignRole(SuperAdminRole::class);
```

## Blade

```blade
@role(SuperAdminRole::class)
    <a href="/admin/danger-zone">Опасная зона</a>
@endrole
```

::: tip
Рекомендуется использовать `SuperAdminInterface` вместо `Gate::before()`, чтобы логика супер-админа была инкапсулирована в пакете.
:::

→ [Рецепт: super-admin wildcard](/ru/guide/recipes/super-admin-wildcard)
