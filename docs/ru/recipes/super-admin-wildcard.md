# Супер-admin Wildcard

Супер-администратор должен проходить **все** проверки прав без явного перечисления.

## Через Gate::before()

```php
// app/Providers/AppServiceProvider.php
Gate::before(function (User $user, string $ability): ?bool {
    return $user->is_super_admin ? true : null;
});
```

## Через роль с wildcard

```php
use AzGuard\Roles\BaseRole;

class SuperAdminRole extends BaseRole
{
    public function permissions(): array
    {
        return ['*']; // AzGuard возвращает true для любой проверки
    }
}

// Назначение (по имени роли — 'super-admin')
$user->assignRole('super-admin');

// Теперь любая проверка возвращает true
$user->hasPermission(PostsPermission::Delete); // true
$user->hasPermission(AdminPermission::Nuke);   // true
```

::: danger
Назначайте роль супер-администратора только через сидеры или CLI. Никогда не давайте UI для самоназначения.
:::
