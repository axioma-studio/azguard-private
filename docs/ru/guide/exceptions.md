# Исключения

## AzGuardException

Базовый класс всех исключений AzGuard.

```php
use AzGuard\Exceptions\AzGuardException;

try {
    $user->assignRole('NonExistentRole');
} catch (AzGuardException $e) {
    Log::error('AzGuard error: ' . $e->getMessage());
}
```

## PermissionDeniedException

Бросается при отказе в доступе через middleware или `#[CheckPermission]`:

```php
use AzGuard\Exceptions\PermissionDeniedException;

// Перехват в Handler.php
public function render($request, Throwable $e): Response
{
    if ($e instanceof PermissionDeniedException) {
        if ($request->expectsJson()) {
            return response()->json([
                'message'    => 'Доступ запрещён.',
                'permission' => $e->getPermission(),
            ], 403);
        }
        return redirect()->route('home')
            ->with('error', 'У вас нет доступа к этому разделу.');
    }

    return parent::render($request, $e);
}
```

## RoleNotFoundException

```php
use AzGuard\Exceptions\RoleNotFoundException;

try {
    $user->assignRole('ghost-role');
} catch (RoleNotFoundException $e) {
    // Роль не зарегистрирована в конфиге
    Log::warning("Попытка назначить несуществующую роль: {$e->getRoleName()}");
}
```

## Типичные ошибки

| Ошибка | Причина | Решение |
|---|---|---|
| `PermissionDeniedException` | Пользователь не имеет права | Назначьте роль или грант |
| `RoleNotFoundException` | Роль не зарегистрирована в Panel | Добавьте класс в `getRoles()` |
| `MigrationNotRunException` | Миграции не выполнены | `php artisan migrate` |
| `TraitNotUsedException` | Нет `HasAzGuard` на модели | Подключите трейт |
