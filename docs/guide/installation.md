# Установка и совместимость

## Требования

| Компонент | Минимальная версия |
|---|---|
| PHP | 8.2 |
| Laravel | 10.x / 11.x / 12.x |
| Filament *(опционально)* | 4.x / 5.x |

## Основной пакет

```bash
composer require azguard/azguard
```

Авто-обнаружение провайдера работает «из коробки» через Laravel Package Discovery.

### Публикация конфига

```bash
php artisan vendor:publish --tag=az-guard-config
```

Файл конфига публикуется в `config/az-guard.php`.

### Публикация миграций *(опционально)*

AzGuard хранит гранты в БД. Если вы хотите кастомизировать миграции — опубликуйте их:

```bash
php artisan vendor:publish --tag=az-guard-migrations
php artisan migrate
```

> По умолчанию миграции запускаются автоматически через `loadMigrationsFrom`.

---

## Матрица совместимости

| AzGuard | Laravel 10 | Laravel 11 | Laravel 12 | PHP 8.2 | PHP 8.3 | PHP 8.4 |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| `0.x` (dev) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

### Filament-пакет

| azguard/filament | Filament 4 | Filament 5 |
|---|:---:|:---:|
| `0.x` (dev) | ✅ | ✅ |

---

## Filament-пакет

```bash
composer require azguard/filament
```

В вашем `AdminPanelProvider` (или любом другом Filament-провайдере) добавьте плагин:

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

Плагин автоматически регистрирует:
- `RoleResource` — управление ролями и их разрешениями
- `DirectGrantResource` — прямые гранты разрешений пользователям

---

## Настройка User-модели

```php
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable
{
    use HasAzGuard;
}
```

Trait добавляет методы: `giveAzPermission()`, `revokeAzPermission()`, `hasAzPermission()`, `assignRole()`, `removeRole()`, `clearAzPermissionsCache()`.

---

## Настройка панелей

Опишите провайдер панели:

```php
// app/AzGuard/Panels/AppPanelProvider.php
use AzGuard\Contracts\PanelProviderInterface;

class AppPanelProvider implements PanelProviderInterface
{
    public function panel(): string
    {
        return 'app';
    }

    public function permissions(): array
    {
        return [
            DocumentsPermission::class,
            ProjectsPermission::class,
        ];
    }

    public function roles(): array
    {
        return [
            EditorRole::class,
            ViewerRole::class,
        ];
    }
}
```

Зарегистрируйте в конфиге:

```php
// config/az-guard.php
'panels' => [
    \App\AzGuard\Panels\AppPanelProvider::class,
],
```

---

## Диагностика

```bash
php artisan azguard:doctor
```

Команда проверит корректность конфигурации, наличие миграций и доступность кэша.
