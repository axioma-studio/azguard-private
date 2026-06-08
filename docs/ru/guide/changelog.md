# Список изменений

Все значимые изменения документируются здесь. AzGuard следует [Semantic Versioning](https://semver.org/).

## 0.x (dev)

> Предрелизная разработка. API стабилен внутри спринта, но между спринтами возможны ломающие изменения до `1.0.0`.

### Sprint 9 — Документация
- Реструктурирована навигация: Introduction / Basic Usage / Best Practices / Advanced
- Новые страницы: `quick-start`, `panels`, `permission-catalog`, `artisan-commands`, `configuration`
- Переписаны: `roles`, `permissions`, `policies-and-gates`, `http-access`, `abilities-frontend`, `comparison`, `filament`, `why-azguard`, `entity-scopes`
- Рецепты вынесены на отдельные страницы

### Sprint 8 — CI / Качество
- Pint + PHPStan level 8 в CI
- Рефакторинг `UserWithDirectGrants`
- Тестовый набор: 7 файлов

### Sprint 7 — Пользовательские роли
- Модель `AzRole` для создания ролей в runtime
- `RoleResource` в Filament: создание/редактирование/удаление ролей, выбор разрешений
- `assignRole($azRoleInstance)` в `HasAzGuard`

### Sprint 6 — Direct Grants
- Модель `AzDirectGrant` с `expires_at` и `revoked_at`
- `DirectGrantResource` в Filament
- `giveAzPermission()` / `revokeAzPermission()` на User
- `artisan azguard:grant`

### Sprint 5 — Интеграция с Filament
- Пакет `azguard/filament`
- `AzGuardPlugin`, базовый класс `AzGuardResource`
- Привязка панелей через `forPanel()`

### Sprint 4 — Entity Scopes
- Трейт `InteractsWithAzScopes`
- `assignScopedRole()`, `removeScopedRole()`, `hasScopedRole()`
- `hasScopedPermission()` с порядком разрешения: wildcard → global → scoped

### Sprint 3 — Контекст
- `AzGuardContext` для проверок с учётом tenant/team
- `withContext()`, нулевые накладные расходы когда не используется

### Sprint 2 — Тестирование
- Pest-хелперы: `actingAsWithRole()`, `assertCanAzPermission()`, `assertCannotAzPermission()`
- Стаб `UserWithDirectGrants`

### Sprint 1 — Ядро
- Трейт `HasAzGuard`
- Система панелей с `PanelProviderInterface`
- Аттрибуты `#[GateAbility]`, `#[CheckPermission]`, `#[RoleOnly]`, `#[SkipGuardCheck]`
- Интеграция `Gate::before`
- `azguard:doctor`, `azguard:sync-roles`, `azguard:cache-reset`
