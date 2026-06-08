# Changelog

All notable changes to AzGuard are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [Unreleased]

---

## Sprint 8 — Context Package

### Added

#### `packages/context` (новый opt-in пакет `axioma-studio/azguard-context`)

- **`AuthorizationContext`** — immutable value object: `panelId` + `contextType` + `contextId`.
  Методы `withPanel()` / `withContext()` возвращают новый экземпляр.
- **`AuthorizationContextManager`** — singleton, хранит активный контекст per-panel на время request.
  Методы: `set()`, `current()`, `has()`, `clear()`, `clearAll()`.
- **`Contracts/ResolvesContext`** — интерфейс resolver-ов для приложения.
- **`Contracts/ContextMergeStrategy`** — интерфейс стратегии объединения global + context прав.
- **`Strategies/GlobalPlusContextStrategy`** — `global ∪ context` (дефолт).
- **`Strategies/ContextOnlyStrategy`** — только context, global игнорируется.
- **`Strategies/DenyWithoutContextStrategy`** — пустой set без контекста.
- **`ContextualRoleGrantSource`** — `implements GrantSource`, приоритет 95,
  читает таблицу `az_guard_context_roles`.
- **`Middleware/SetAuthorizationContext`** (`azguard.context`) — запускает resolvers,
  устанавливает контекст в manager.
- **`AzGuardContextServiceProvider`** — регистрирует singleton, стратегию, middleware, миграции.
- **`config/az-guard-context.php`** — `merge_strategy` + `resolvers`.
- **Миграция** `create_az_guard_context_roles_table` — таблица с индексами и unique-constraint.
- **`README.md`** для пакета с примером resolver + middleware.

#### `packages/core` — расширение `HasAzGuard`

- **`hasAzPermission(string, string, ?object $context)`** — новый опциональный 3-й аргумент.
  Duck-typed `$context` с полями `contextType` + `contextId`.
  Полная обратная совместимость: без `$context` поведение идентично предыдущей версии.
- **`hasAzPermissionIn(string $contextType, int|string $contextId, string $permission, string $panelId)`** —
  новый удобный alias для контекстных проверок без изменения глобального состояния.
- **`checkAzPermission(string, string, ?object $context)`** — проксирует новую сигнатуру.
- **`Support/AzGuardContextBridge`** (новый) — thin adapter, изолирует зависимость core от packages/context
  через `class_exists()` guard. Методы: `checkWithContext()`, `checkInContext()`,
  `resolveWithIsolatedContext()` (идемпотентен, восстанавливает контекст через `try/finally`).

#### Docs

- **`docs/guide/context.md`** — полное руководство по пакету context:
  быстрый старт, все виды проверок, выдача прав, стратегии, приоритеты, обратная совместимость.

---

## Sprint 7 — Filament Role Toggle

### Added

- **`RoleResource`**: toggle «Управляется PHP-классом» (`live()`), поле `class_name` (visible по toggle),
  info-плашка при скрытой вкладке прав.
- **Колонка «Тип»** в таблице ролей: иконка `code-bracket` (warning) для code roles,
  `circle-stack` (success) для custom; tooltip показывает FQCN или «права из БД».
- **`TernaryFilter`** — фильтрация «Все / Code roles / Custom roles».

---

## Sprint 6 — Database Roles & Direct Grants

### Added

- `DatabaseRoleGrantSource` (приоритет 90) — права из таблицы `az_guard_role_permissions`.
- `DirectGrantSource` (приоритет 80) — прямые гранты из `az_guard_direct_grants`.
- `HasDirectGrants` trait — `grantAzPermission()`, `revokeAzPermission()`, `syncAzPermissions()`.
- Миграции: `az_guard_role_permissions`, `az_guard_direct_grants`.
- Filament: `RolePermissionsRelationManager`, скрывается при `class_name !== null`.

---

## Sprint 1–5 — Core Foundation

### Added

- `packages/core`: `PermissionSet`, `PermissionCatalog`, `EffectivePermissionResolver`,
  `PermissionResolverCache`, `ClassRoleGrantSource`.
- `HasAzGuard` trait: `roles()`, `hasAzPermission()`, `hasAzRole()`,
  `assignRole()`, `removeRole()`, `syncRoles()`.
- `AzGuardServiceProvider`, конфиг `az-guard.php`, базовые миграции.
- Filament: `RoleResource`, `AzGuardPlugin`, `PanelAuthorizationMiddleware`.
- `@azcan` / `@endazcan` Blade-директивы.
