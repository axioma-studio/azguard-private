# Быстрый старт

## Установка

```bash
composer require azguard/azguard
```

## User

```php
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable
{
    use HasAzGuard;
}
```

## Панель

```bash
php artisan make:guard-panel
```

Зарегистрируйте провайдер в `config/az-guard.php` → `panels`.

## Маршруты (app)

```php
Route::middleware(['auth', 'azguard.panel:app', 'azguard.roles', 'check.access'])->group(...);
```

## Контроллер

```php
use AzGuard\Attributes\CheckPermission;

#[CheckPermission(permission: DocumentsPermission::View, arguments: ['document'])]
public function show(Document $document) { ... }
```

См. [concept.md](concept.md).
