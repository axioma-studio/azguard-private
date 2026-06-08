# AzGuard

[![Tests](https://github.com/axioma-studio/azguard-private/actions/workflows/tests.yml/badge.svg)](https://github.com/axioma-studio/azguard-private/actions/workflows/tests.yml)
[![Lint](https://github.com/axioma-studio/azguard-private/actions/workflows/lint.yml/badge.svg)](https://github.com/axioma-studio/azguard-private/actions/workflows/lint.yml)
[![Static Analysis](https://github.com/axioma-studio/azguard-private/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/axioma-studio/azguard-private/actions/workflows/static-analysis.yml)

**Code-first RBAC** для Laravel с поддержкой multi-panel архитектуры и PHP Attributes.

---

## Почему AzGuard, а не Spatie?

| Критерий | AzGuard | spatie/permission |
|---|---|---|
| Подход | **Code-first** (PHP-классы) | Database-driven |
| Версионирование прав | Git (вместе с кодом) | БД (мигрирует отдельно) |
| Multi-panel | ✅ Встроено | ❌ |
| PHP Attributes | ✅ `#[CheckPermission]`, `#[GateAbility]` | ❌ |
| Policy autodiscovery | ✅ | ❌ |
| Кэш масштабируемость | Конфигурируемый store | Вся таблица в кэше |

---

## Установка

```bash
composer require azguard/azguard
php artisan vendor:publish --tag=az-guard-config
php artisan migrate
```

---

## Быстрый старт

### 1. Добавьте trait к User-модели

```php
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable
{
    use HasAzGuard;
}
```

### 2. Создайте роль

```bash
php artisan azguard:make-role Admin --panel=app
```

Это создаст `app/Guards/App/Roles/AdminRole.php`:

```php
class AdminRole extends BaseRole
{
    public function permissions(): array
    {
        return [
            AppPermission::UsersView->value,
            AppPermission::UsersCreate->value,
        ];
    }
}
```

### 3. Создайте панель

```bash
php artisan azguard:make-panel App
```

### 4. Назначьте роль пользователю

```php
$role = Role::where('name', 'admin')->first();
$user->roles()->attach($role);
```

### 5. Проверяйте права

```php
// В контроллере / политике
$user->hasAzPermission('app.users.view');

// Через Gate
Gate::allows('app.users.create');

// В Blade
@azcan('app.users.view')
    <a href="/users">Пользователи</a>
@endazcan

@azrole('admin')
    <span>Администратор</span>
@endazrole
```

---

## Команды

| Команда | Описание |
|---|---|
| `azguard:doctor` | Диагностика конфигурации |
| `azguard:list-permissions {panel?}` | Список всех зарегистрированных прав |
| `azguard:cache-reset` | Сброс кэша прав |
| `azguard:make-panel {name}` | Создать панель |
| `azguard:make-role {name} --panel=` | Создать роль |
| `azguard:make-permission {name} --panel=` | Создать enum разрешений |
| `azguard:make-policy {name} --panel=` | Создать политику |

---

## Кэш

По умолчанию права кэшируются только in-memory (в рамках одного запроса).
Для cross-request кэша через Redis:

```php
// config/az-guard.php
'cache' => [
    'store'           => 'redis',
    'expiration_time' => 3600,
    'key'             => 'azguard.permissions',
],
```

Для сброса кэша: `php artisan azguard:cache-reset`

---

## Структура монорепозитория

```
azguard-private/
├── packages/
│   ├── core/      # azguard/azguard — основной пакет
│   └── filament/  # azguard/filament — Filament-интеграция
├── tests/         # Feature + Unit + Arch тесты
├── .github/
│   └── workflows/ # CI: tests, lint, static-analysis
└── composer.json
```

---

## Разработка

```bash
# Тесты
composer test

# Тесты с покрытием (минимум 80%)
composer test:coverage

# Только Unit
composer test:unit

# Только Feature
composer test:feature

# Code style
composer lint

# Статический анализ
composer analyse

# Мутационное тестирование
composer mutate
```

---

## Лицензия

MIT
