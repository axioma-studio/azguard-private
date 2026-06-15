# AzGuard

[![Tests](https://github.com/axioma-studio/azguard/actions/workflows/tests.yml/badge.svg)](https://github.com/axioma-studio/azguard/actions/workflows/tests.yml)
[![Code Style](https://github.com/axioma-studio/azguard/actions/workflows/code-style.yml/badge.svg)](https://github.com/axioma-studio/azguard/actions/workflows/code-style.yml)
[![Static Analysis](https://github.com/axioma-studio/azguard/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/axioma-studio/azguard/actions/workflows/static-analysis.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

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
composer require axioma-studio/azguard-core
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
php artisan make:guard-role
```

Команда интерактивная: выберите панель и введите имя роли. Это создаст
`app/Guards/App/Roles/AdminRole.php`:

```php
class AdminRole extends BaseRole
{
    public function permissions(): array
    {
        return [
            'app.users.view',
            'app.users.create',
        ];
    }
}
```

### 3. Создайте панель

```bash
php artisan make:guard-panel
```

### 4. Назначьте роль пользователю

```php
$user->assignRole('admin');
```

### 5. Проверяйте права

```php
// В контроллере / политике
$user->hasPermission('app.users.view');

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
| `guard:doctor` | Диагностика конфигурации |
| `guard:list-permissions {panel?}` | Список всех зарегистрированных прав |
| `guard:cache-reset` | Сброс кэша прав |
| `make:guard-panel` | Создать панель |
| `make:guard-role` | Создать роль |
| `make:guard-permission` | Создать enum разрешений |
| `make:guard-policy` | Создать политику |

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

Для сброса кэша: `php artisan guard:cache-reset`

---

## Структура монорепозитория

```
azguard-private/
├── packages/
│   ├── core/      # axioma-studio/azguard-core — основной пакет
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
