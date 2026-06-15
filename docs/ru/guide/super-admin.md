# Супер-администратор

Супер-админ — пользователь, который **автоматически имеет все права** без явного перечисления.

## Настройка

### Через роль с wildcard

Класс-роль возвращает `['*']` из `permissions()` — AzGuard трактует это как «все права»:

```php
// app/AzGuard/App/Roles/SuperAdminRole.php
use AzGuard\Roles\BaseRole;

class SuperAdminRole extends BaseRole
{
    public function permissions(): array
    {
        return ['*']; // wildcard: проходит любую проверку
    }
}
```

Имя такой роли — `super-admin` (выводится из имени класса).

### Через Gate::before()

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::before(function ($user, $ability) {
        if ($user->hasRole('super-admin')) {
            return true;  // пропустить любой Gate-чек
        }
    });
}
```

::: tip
Wildcard-роль работает и для `hasPermission()`, и для `Gate::allows()` (AzGuard регистрируется через `Gate::before`), поэтому отдельный хук обычно не нужен.
:::

## Назначение

```php
$user->assignRole('super-admin');
```

## Blade

```blade
@azrole('super-admin')
    <a href="/admin/danger-zone">Опасная зона</a>
@endazrole
```

::: tip
`guard:doctor` поможет проверить, что роль `super-admin` зарегистрирована и синхронизирована с БД.
:::

→ [Рецепт: super-admin wildcard](/ru/guide/recipes/super-admin-wildcard)
