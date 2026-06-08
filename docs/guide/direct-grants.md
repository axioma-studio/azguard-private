# Direct Grants

Direct Grants — механизм выдачи прав **напрямую пользователю** без ролей. Используйте их, когда нужно выдать одно право конкретному пользователю, не создавая отдельную роль. Типичные сценарии: временный доступ, экспорт по запросу, бета-функциональность для выбранных пользователей.

## Подключение трейта

Добавьте `HasDirectGrants` в User-модель рядом с `HasAzGuard`:

```php
use AzGuard\Concerns\HasAzGuard;
use AzGuard\Concerns\HasDirectGrants;

class User extends Authenticatable
{
    use HasAzGuard, HasDirectGrants;
}
```

::: tip
`HasDirectGrants` расширяет `hasAzPermission()`: теперь он проверяет роль **или** direct grant — остальной код менять не нужно.
:::

## Выдача grant

### Fluent API

```php
use AzGuard\Facades\AzGuard;

// Бессрочно
AzGuard::forUser($user)
    ->on('app')
    ->give('app.documents.export');

// С TTL 1 час
AzGuard::forUser($user)
    ->on('app')
    ->ttl(3600)
    ->give('app.documents.export');

// Короткий способ
AzGuard::grantDirect($user, 'app.documents.export', 'app', ttl: 3600);
```

::: info Idempotency
Повторный вызов `give()` обновляет `expires_at` без создания дубликата. Безопасно вызывать несколько раз.
:::

### Artisan CLI

```bash
# Выдать бессрочно
php artisan az-guard:grant {user-id} {permission} {panel}

# Выдать на 1 час
php artisan az-guard:grant 42 app.documents.export app --ttl=3600

# Другая модель
php artisan az-guard:grant 7 admin.reports.view admin --model=App\\Models\\Admin
```

## Отзыв grant

```php
// Один ключ
AzGuard::forUser($user)->on('app')->revoke('app.documents.export');
AzGuard::revokeDirect($user, 'app.documents.export', 'app');

// Все grants панели
AzGuard::forUser($user)->on('app')->revokeAll();
```

```bash
# Artisan
php artisan az-guard:revoke-grant 42 app.documents.export app
php artisan az-guard:revoke-grant 42 - app --all --force
```

## Проверка наличия grant

```php
// На User-модели
$user->hasDirectGrant('app.documents.export');          // текущая панель
$user->hasDirectGrant('app.documents.export', 'app');   // явная панель

// Через Laravel Gate
Gate::allows('direct-grant', 'app.documents.export');
Gate::allows('direct-grant', ['app.documents.export', 'app']);

// Список активных grants
$grants = AzGuard::forUser($user)->on('app')->list();
$grants = AzGuard::activeGrants($user, 'app');
```

## Темплейты Blade

```blade
{{-- Проверка direct grant (текущая панель) --}}
@azdirect('app.documents.export')
    <button>Export</button>
@endazdirect

{{-- Явная панель --}}
@azdirect('app.documents.export', 'app')
    <button>Export</button>
@endazdirect

{{-- Для сравнения: роли --}}
@azcan('app.documents.view')
    <a href="/docs">Documents</a>
@endazcan
```

## Route Middleware

```php
use Illuminate\Support\Facades\Route;

// az.grant:{permission},{panel}
Route::get('/export', ExportController::class)
    ->middleware('az.grant:app.documents.export,app');

// Панель из AzGuard::currentPanel() если не указана:
Route::get('/export', ExportController::class)
    ->middleware('az.grant:app.documents.export');
```

| Ситуация | HTTP |
|---|---|
| Не аутентифицирован | 401 |
| Grant отсутствует или истёк | 403 |
| Grant активен | 200 |

## TTL и истечение

Grant с `expires_at < now()` автоматически считается недействительным во всех проверках. Старые записи очищает планировщик:

```php
// bootstrap/app.php
->withSchedule(function (Schedule $schedule) {
    $schedule->command('az-guard:prune-grants')->daily();
})
```

Или вручную:

```bash
php artisan az-guard:prune-grants
php artisan az-guard:prune-grants --panel=app
```

## События

| Событие | Когда диспатчится |
|---|---|
| `GrantGiven` | После каждого `give()` |
| `GrantRevoked` | После каждого `revoke()` / `revokeAll()` |

```php
use AzGuard\Events\GrantGiven;
use AzGuard\Events\GrantRevoked;
use Illuminate\Support\Facades\Event;

Event::listen(GrantGiven::class, function (GrantGiven $event): void {
    Log::info("Grant [{$event->permissionKey}] выдан user #{$event->user->getAuthIdentifier()}");
});

Event::listen(GrantRevoked::class, function (GrantRevoked $event): void {
    // например, инвалидация кэша API
});
```

## Быстрый справочник

| Способ | API |
|---|---|
| Fluent (fluent-цепочка) | `AzGuard::forUser($u)->on('app')->ttl(3600)->give('...')` |
| Короткий хелпер | `AzGuard::grantDirect($u, '...', 'app', ttl: 3600)` |
| Artisan | `php artisan az-guard:grant {id} {perm} {panel}` |
| Blade | `@azdirect('app.x.view') ... @endazdirect` |
| Middleware | `->middleware('az.grant:app.x.view,app')` |
| Gate | `Gate::allows('direct-grant', 'app.x.view')` |
| User-модель | `$user->hasDirectGrant('app.x.view', 'app')` |
