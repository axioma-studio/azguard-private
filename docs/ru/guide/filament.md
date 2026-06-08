# Интеграция с Filament

AzGuard предоставляет первоклассную интеграцию с [Filament v3](https://filamentphp.com).

## Установка

```bash
composer require axioma-studio/azguard-filament
```

## Регистрация плагина

```php
// app/Providers/Filament/AdminPanelProvider.php
use AzGuard\Filament\AzGuardFilamentPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->id('admin')
        ->path('admin')
        ->authGuard('admin')
        ->plugin(
            AzGuardFilamentPlugin::make()
                ->panel('admin')  // соответствует панели AzGuard
        );
}
```

## Resources и проверка прав

```php
use AzGuard\Filament\Concerns\HasAzGuardPolicy;

class PostResource extends Resource
{
    use HasAzGuardPolicy;

    // Опционально: сопоставление действий Filament с enum-кейсами
    protected static array $permissionMap = [
        'viewAny' => PostsPermission::View,
        'create'  => PostsPermission::Create,
        'update'  => PostsPermission::Edit,
        'delete'  => PostsPermission::Delete,
    ];
}
```

## Навигация с учётом прав

```php
// Пункты меню автоматически скрываются при отсутствии прав на viewAny
NavigationItem::make()
    ->label('Пользователи')
    ->url('/admin/users')
    ->visible(fn() => auth()->user()?->hasPermission(UsersPermission::View))
```

→ [Несколько Guards](/ru/guide/multiple-guards)
