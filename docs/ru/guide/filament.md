# Интеграция с Filament

AzGuard интегрируется с Filament v3 через панельный провайдер.

## Настройка

```php
// app/Providers/Filament/AdminPanelProvider.php
public function panel(Panel $panel): Panel
{
    return $panel
        ->authMiddleware([
            \AzGuard\Http\Middleware\CheckPermission::class,
        ]);
}
```

## Проверка прав в ресурсах

```php
use App\AzGuard\Admin\Permissions\UsersPermission;

class UserResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermission(UsersPermission::View) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasPermission(UsersPermission::Create) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasPermission(UsersPermission::Edit) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasPermission(UsersPermission::Delete) ?? false;
    }
}
```

## Навигация по ролям

```php
use Filament\Navigation\NavigationItem;

NavigationItem::make('Пользователи')
    ->visible(fn () => auth()->user()?->hasPermission(UsersPermission::View))
    ->url(UserResource::getUrl());
```

## Пример: AdminPanel

```php
// app/AzGuard/Admin/AdminPanel.php
namespace App\AzGuard\Admin;

use AzGuard\Contracts\PanelInterface;

class AdminPanel implements PanelInterface
{
    public function getName(): string { return 'admin'; }

    public function getRoles(): array
    {
        return [
            Roles\AdminRole::class,
            Roles\ModeratorRole::class,
        ];
    }
}
```
