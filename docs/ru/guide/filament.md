# Интеграция с Filament

AzGuard предоставляет первоклассную интеграцию с [Filament v5](https://filamentphp.com).

## Установка

```bash
composer require axioma-studio/azguard-filament
```

## Регистрация плагина

```php
// app/Providers/Filament/AdminPanelProvider.php
use AzGuard\Filament\AzGuardPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->id('admin')
        ->path('admin')
        ->authGuard('admin')
        ->plugin(
            AzGuardPlugin::make()
                ->forPanel('admin')  // id панели AzGuard из каталога прав
        );
}
```

Плагин добавляет ресурсы управления ролями и прямыми грантами, а также страницу
диагностики (`DoctorPage`).

## Авторизация без кода

При `enforce => true` (по умолчанию) плагин авторизует **каждый** обнаруженный
ресурс по сгенерированному праву — никакого кода в самих ресурсах не требуется.
AzGuard отключает «shortcut» Filament по наличию политики и отвечает на проверки
Gate из прав пользователя AzGuard.

```php
// config/az-guard-filament.php
return [
    'panel'   => 'admin',     // панель AzGuard, питающая каталог прав
    'enforce' => true,        // авторизовать ресурсы автоматически
    'source'  => 'database',  // 'database' | 'enum' | 'policy'
];
```

- `database` — права проверяет рантайм-gate, ничего не генерируется;
- `enum` — `guard:filament:generate` пишет enum прав на каждый ресурс;
- `policy` — `guard:filament:generate` пишет Laravel-политику на каждый ресурс.

## Генерация enum / политик

```bash
php artisan guard:filament:generate
php artisan guard:filament:generate --source=enum --panel=admin --dry-run
```

## Навигация с учётом прав

```php
// Пункты меню скрываются при отсутствии прав
NavigationItem::make()
    ->label('Пользователи')
    ->url('/admin/users')
    ->visible(fn () => auth()->user()?->hasPermission(UsersPermission::View, 'admin'))
```

→ [Несколько Guards](/ru/guide/multiple-guards)
