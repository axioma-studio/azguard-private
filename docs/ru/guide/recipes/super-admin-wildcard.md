# Супер-admin через wildcard

## Через Gate::before

```php
// AppServiceProvider
Gate::before(function ($user, $ability) {
    if ($user->hasRole(SuperAdminRole::class)) {
        return true;
    }
});
```

## Через WildcardRole

```php
class SuperAdminRole implements RoleInterface
{
    public function permissions(): array
    {
        return ['*']; // все текущие и будущие права
    }
}
```

::: danger
Wildcard даёт доступ ко **всем** правам включая будущие. Используйте только для системных аккаунтов.
:::

## Ограниченный супер-admin

```php
Gate::before(function ($user, $ability) {
    // Суперадмин не может удалять аудит-логи
    if (str_starts_with($ability, 'audit.')) return null;

    if ($user->hasRole(SuperAdminRole::class)) return true;
});
```
