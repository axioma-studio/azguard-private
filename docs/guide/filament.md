# Filament + AzGuard

Пакет `azguard/filament` даёт базовый `GuardResource` с проверкой admin-permissions.

## Подключение

```json
"azguard/filament": "@dev"
```

## Resource

```php
use AzGuard\Filament\Resources\GuardResource;

final class UserResource extends GuardResource
{
    protected static function guardPanel(): string
    {
        return 'admin';
    }

    protected static function viewPermission(): UnitEnum
    {
        return AdminPermission::UsersManage;
    }
}
```

`canViewAny()` / `canCreate()` / `canEdit()` — через `Gate::allows` и `AzGuard::permission()`.

## Инвариант

В Filament проверяются только permissions панели `admin`, не app-роли.
