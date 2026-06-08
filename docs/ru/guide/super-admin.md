# Супер-администратор

Супер-администратор обходит все проверки разрешений. AzGuard реализует это через `Gate::before()`.

## Через Gate::before()

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    Gate::before(function (User $user, string $ability): ?bool {
        if ($user->isSuperAdmin()) {
            return true;  // пропустить все дальнейшие проверки
        }
        return null;  // продолжить обычную проверку
    });
}
```

## Через роль с wildcard

```php
class SuperAdminRole implements RoleInterface
{
    public function getName(): string { return 'super-admin'; }

    public function permissions(): array
    {
        // Возвращаем wildcard — AzGuard трактует его как «всё разрешено»
        return [WildcardPermission::All];
    }
}
```

## Через атрибут модели

```php
// В миграции добавьте колонку
$table->boolean('is_super_admin')->default(false);

// В Gate::before()
Gate::before(fn (User $user) => $user->is_super_admin ?: null);
```

::: warning Безопасность
Назначайте роль супер-администратора только в сидерах или через миграции. Никогда не создавайте UI для самоназначения.
:::

::: tip Тестирование
В тестах используйте `actingAs($superAdmin)` и проверяйте, что права действительно обходятся:
```php
$this->actingAs($superAdmin)
     ->get('/admin/settings')
     ->assertOk();
```
:::
