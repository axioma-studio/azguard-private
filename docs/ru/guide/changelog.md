# Список изменений

Полный changelog доступен на [GitHub Releases](https://github.com/axioma-studio/azguard-private/releases).

## [Unreleased]

- i18n: документация на русском языке
- Улучшенная структура навигации

## [1.0.0] — 2026

### Добавлено
- Code-first RBAC: права как PHP enum, роли как PHP-классы
- Мультипанельная изоляция (`app.*`, `admin.*`, `api.*`)
- Прямые гранты с TTL
- Контекстные проверки (tenant, team, project)
- Интеграция с Laravel Gate через `Gate::before()`
- Атрибут `#[CheckPermission]` для контроллеров
- Команды `azguard:doctor` и `azguard:sync-roles`
- Поддержка UUID/ULID
- Интеграция с Filament
