# Интеграция с Filament

## Установка

```bash
composer require axioma-studio/azguard-filament
```

## Конфигурация панели

```php
// app/Providers/Filament/AdminPanelProvider.php
use AzGuard\Filament\AzGuardPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(AzGuardPlugin::make()->panel('admin'));
}
```

## Защита ресурсов

```php
use AzGuard\Filament\Concerns\HasAzGuardPermissions;

class PostResource extends Resource
{
    use HasAzGuardPermissions;

    protected static string $permissionEnum = PostsPermission::class;

    public static function canCreate(): bool
    {
        return auth()->user()->hasPermission(PostsPermission::Create);
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->hasPermission(PostsPermission::Edit);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->hasPermission(PostsPermission::Delete);
    }
}
```

## Управление ролями через UI

Плагин добавляет страницу «Роли и права» в вашу Filament-панель:

```php
AzGuardPlugin::make()
    ->panel('admin')
    ->showRolesPage()      // страница управления ролями
    ->showGrantsPage();    // страница прямых грантов
```
