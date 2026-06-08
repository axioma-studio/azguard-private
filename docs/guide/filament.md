# Filament + AzGuard

Пакет `azguard/filament` предоставляет UI для управления ролями и прямыми грантами прямо из Filament-админки.

## Установка

```bash
composer require azguard/filament
```

Добавьте плагин в ваш Filament Panel Provider:

```php
use AzGuard\Filament\AzGuardPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            AzGuardPlugin::make()->forPanel('admin'),
        ]);
}
```

> `forPanel('admin')` указывает, в контексте какой AzGuard-панели работает Filament.
> По умолчанию — `'app'`.

## Ресурсы

### RoleResource

Позволяет создавать, редактировать и удалять роли. Во вкладке **Permissions** через `RolePermissionsRelationManager` можно
напрямую привязывать permission-строки к роли.

### DirectGrantResource

Отображает все прямые гранты пользователям. Поддерживает создание, редактирование и удаление грантов с фильтрацией по пользователю и панели.

## Защита ресурсов через GuardResource

Если вам нужно ограничить доступ к собственным Filament Resource-ам через AzGuard, наследуйтесь от `GuardResource`:

```php
use AzGuard\Filament\Resources\AzGuardResource;

final class UserResource extends AzGuardResource
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

Методы `canViewAny()`, `canCreate()`, `canEdit()`, `canDelete()` автоматически проксируются
через `Gate::allows` + `AzGuard::permission()`.

## Совместимость

| azguard/filament | Filament 4 | Filament 5 |
|---|:---:|:---:|
| `0.x` (dev) | ✅ | ✅ |

## Инвариант

Filament-пакет проверяет только permissions панели, указанной в `forPanel()`. Роли уровня `app`
не пересекаются с правами Filament-админки.

## Далее

- [Установка и совместимость](installation.md) — полная матрица версий
- [Прямые гранты](direct-grants.md) — выдача прав без роли
- [Роли](roles.md) — code-first описание ролей
