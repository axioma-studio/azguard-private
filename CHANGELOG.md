# Changelog

Все заметные изменения в проекте документируются здесь.

Формат основан на [Keep a Changelog](https://keepachangelog.com/ru/1.0.0/).
Проект следует [Semantic Versioning](https://semver.org/).

---

## [Unreleased]

### Added
- `AzGuardManagerInterface` — контракт для менеджера панелей, поддерживает mock в тестах
- Blade-директивы `@azcan` / `@endazcan` и `@azrole` / `@endazrole`
- Artisan-команда `azguard:list-permissions` — вывод всех прав по панелям
- Artisan-команда `azguard:cache-reset` — сброс кэша разрешений
- Configurable cache store в `az-guard.php`: `cache.store`, `cache.expiration_time`, `cache.key`
- Feature-флаги в конфиге: `features.wildcard_permission`, `features.teams`, `features.audit_log`
- Wildcard-паттерны (`admin.*`) при включённом `features.wildcard_permission`
- Cross-request кэш прав через любой Laravel cache driver (Redis, file и др.)
- Метод `clearAzPermissionsCache()` в trait `HasAzGuard`
- GitHub Actions CI: матрица PHP 8.2–8.4 × Laravel 10–12
- GitHub Actions: Pint + Rector lint workflow
- GitHub Actions: PHPStan / Larastan static analysis
- Unit-тесты для `Panel`, `Authorizer`, `BaseRole`, `HasAzGuard`, `AzGuardManager`
- Architecture-тесты (Pest arch testing)
- PHPStan конфигурация `phpstan.neon` (level 6)
- `infection/infection` для мутационного тестирования
- `composer scripts`: `test:unit`, `test:feature`, `test:coverage`, `analyse`, `mutate`
- README.md с документацией и сравнением с конкурентами

### Changed
- `Authorizer::check()` теперь использует параметр `$ability` для точной проверки прав
- `HasAzGuard::getAzPermissions()` поддерживает cross-request кэш при `store != 'array'`
- `AzGuardManager` теперь реализует `AzGuardManagerInterface`
- `AzGuardServiceProvider` биндит `AzGuardManagerInterface` → `AzGuardManager` в контейнере
- Расширен `az-guard.php`: добавлены секции `column_names`, `cache`, `features`, `teams`

### Fixed
- `Authorizer::check()` больше не игнорирует параметр `$ability` через `unset()`

---

## [0.1.0] — 2026-01-01

### Added
- Первоначальный релиз AzGuard
- Code-first RBAC через PHP-классы ролей
- Multi-panel архитектура с scoped permissions
- PHP Attributes: `#[CheckPermission]`, `#[GateAbility]`, `#[GuardPolicy]`, `#[RoleOnly]`, `#[SkipGuardCheck]`
- Policy autodiscovery через `PolicyDiscovery`
- Artisan-команды: `azguard:doctor`, `azguard:make-panel`, `azguard:make-role`, `azguard:make-permission`, `azguard:make-policy`, `azguard:make-abilities`
- Middleware: `azguard.roles`, `azguard.panel`, `azguard.check`
- Trait `HasAzGuard` с in-memory кэшем прав
- `GuardDoctor` для диагностики конфигурации
