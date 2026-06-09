# Супер-admin Wildcard

Супер-администратор должен проходить **все** проверки прав без явного перечисления.

## Через Gate::before()

```php
// app/Providers/AppServiceProvider.php
Gate::before(function (User $user, string $ability): ?bool {
    return $user->is_super_admin ? true : null;
});
```

## Через роль с WildcardPermission

```php
class SuperAdminRole implements RoleInterface
{
    public function getName(): string { return 'super-admin'; }

    public function permissions(): array
    {
        return [WildcardPermission::All]; // AzGuard возвращает true для любой проверки
    }
}

// Назначение
$user->assignRole(SuperAdminRole::class);

// Теперь любая проверка возвращает true
$user->hasPermission(PostsPermission::Delete); // true
$user->hasPermission(AdminPermission::Nuke);   // true
```

::: danger
Назначайте роль супер-администратора только через сидеры или CLI. Никогда не давайте UI для самоназначения.
:::
