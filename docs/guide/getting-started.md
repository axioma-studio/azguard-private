# Быстрый старт

Минимальный путь от установки до первой проверки прав — 5 шагов.

## 1. Установка

```bash
composer require azguard/azguard
```

Публикуйте конфиг и запустите миграции:

```bash
php artisan vendor:publish --tag=az-guard-config
php artisan migrate
```

## 2. User-модель

```php
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable
{
    use HasAzGuard;
}
```

## 3. Создайте панель

```bash
php artisan azguard:make-panel App
```

Зарегистрируйте провайдер в `config/az-guard.php` → `panels`.

## 4. Создайте permission

```bash
php artisan azguard:make-permission DocumentsPermission --panel=app
```

```php
enum DocumentsPermission: string implements PermissionInterface
{
    case View   = 'documents.view';
    case Create = 'documents.create';
    case Edit   = 'documents.edit';
    case Delete = 'documents.delete';
}
```

## 5. Проверка прав

Через атрибут на контроллере:

```php
use AzGuard\Attributes\CheckPermission;

#[CheckPermission(permission: DocumentsPermission::View, arguments: ['document'])]
public function show(Document $document) { ... }
```

Через Gate:

```php
Gate::allows('documents.view', $document);
```

Через helper:

```php
if ($user->hasAzPermission(DocumentsPermission::View)) {
    // ...
}
```

Через Blade:

```blade
@azcan('documents.view')
    <a href="...">Просмотр</a>
@endazcan
```

## Далее

- [Установка и совместимость](installation.md) — полная матрица версий
- [Концепция](concept.md) — как работает code-first RBAC
- [Роли](roles.md) — создание и назначение ролей
- [Прямые гранты](direct-grants.md) — выдача прав без роли
- [Filament](filament.md) — UI для управления правами
