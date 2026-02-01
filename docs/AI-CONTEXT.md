# Контекст для AI (AzGuard)

Файл можно подключать в чат через `@docs/AI-CONTEXT.md`, если нужна краткая выжимка по проекту.

## Что такое AzGuard
Пакет авторизации для Laravel: роли, скоупы, привязка доступа к «панелям» (например, админка). Регистрация через `Gate::before` и провайдер; модели — `Role`, `ModelHasScope`; контракты — `RoleInterface`, `ScopeInterface`.

## Где что искать
| Задача | Место |
|--------|--------|
| Регистрация сервисов, команд, миграций | `packages/core/src/AzGuardServiceProvider.php` |
| Конфиг по умолчанию | `packages/core/config/az-guard.php` |
| Миграции БД | `packages/core/database/migrations/` |
| Модели и контракты | `packages/core/src/Models/`, `packages/core/src/Contracts/` |
| Логика Guard (авторизация, панели) | `packages/core/src/Guard/` |
| Trait для моделей (роли/скоупы) | `packages/core/src/Concerns/HasAzGuard.php`, `InteractsWithAzScopes.php` |
| Artisan-команды | `packages/core/src/Commands/` |
| Документация для пользователей | `docs/guide/` |
| Разработка и тесты | `DEVELOPMENT.md`, корневой `composer.json` (scripts) |

## Важно при правках
- Не добавлять маршруты/контроллеры приложения в корень — это пакет.
- Зависимости пакета указывать в `packages/core/composer.json` или `packages/filament/composer.json`, не в корне (кроме dev-инструментов в корне).
- После изменений имеет смысл запускать `composer lint` и `composer test` из корня.
