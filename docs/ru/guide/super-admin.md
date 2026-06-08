# Супер-администратор

Супер-администратор обходит **все** проверки прав. Это реализуется через `Gate::before()` — стандартный механизм Laravel.

## Реализация

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::before(function ($user, $ability) {
        if ($user->hasRole(\App\AzGuard\App\Roles\SuperAdminRole::class)) {
            return true; // пропустить все проверки
        }
    });
}
```

## Альтернатива через WildcardRole

```php
class SuperAdminRole implements RoleInterface
{
    public function permissions(): array
    {
        return ['*']; // wildcard — все права
    }
}
```

::: danger Осторожно
Wildcard `'*'` предоставляет доступ ко **всем** текущим и **будущим** правам. Используйте только для системных пользователей, не для обычных администраторов.
:::

## Исключение из проверки

Если нужно исключить конкретные маршруты из Gate:

```php
Gate::before(function ($user, $ability) {
    // Суперадмин не может удалять аудит-логи
    if ($ability === 'admin.audit.delete') {
        return null; // продолжить стандартную проверку
    }
    if ($user->hasRole(SuperAdminRole::class)) {
        return true;
    }
});
```
