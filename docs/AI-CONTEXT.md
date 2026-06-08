# AI Context — AzGuard

## Sprint Status

| Sprint | Branch | PR | Status |
|--------|--------|----|--------|
| Sprint 1 | `feat/sprint-1-core` | PR #1/#2 | ✅ Merged |
| Sprint 2 | `feat/sprint-2-tests` | [PR #3](https://github.com/axioma-studio/azguard-private/pull/3) | ⏳ Ready for Review |
| Sprint 3 | `feat/sprint-3-docs` | [PR #5](https://github.com/axioma-studio/azguard-private/pull/5) | ⏳ Ready for Review |
| Sprint 4 | `feat/sprint-4-entity-scopes` | [PR #4](https://github.com/axioma-studio/azguard-private/pull/4) | ⏳ Ready for Review |
| Sprint 5 | `feat/sprint-5-ci` | — | 🟡 In Progress |

---

## Что есть в пакете

- Gate, миграции, `Role`, `HasAzGuard`, `Panel`, `PanelProvider`
- `PolicyAttributeRegistrar`, `GateAbility`, `GuardPolicy`, `CheckPermission`, `CheckAccess`
- `AuthorizesPermission`, `SkipGuardCheck`, `RoleOnly`
- `resolvePermission`, `AzGuard::permission()`, `guard:doctor`
- Generators: `make:guard-panel`, `make:guard-permission`, `make:guard-policy`, `make:guard-abilities`, `make:guard-role`
- `AbilitiesDto`, `azguard/filament` (`GuardResource`)
- **Sprint 4**: `InteractsWithAzScopes` — entity-scoped roles API (`assignScopedRole`, `removeScopedRole`, `hasScopedRole`, `hasScopedPermission`)

## Не в пакете

- Модели и enum домена приложения
- Inertia shared layout (глобальные permissions) — по решению приложения

## Контракт роли

| Поле | Значение |
|------|----------|
| `roles.name` | slug |
| `roles.class_name` | FQCN класса роли |
| `roles.level` | уровень приоритета (0 = min) |

## Контракт entity-scoped роли (Sprint 4)

```php
// Назначить роль в рамках сущности
$user->assignScopedRole('editor', $project);

// Проверить право в рамках сущности
$user->hasScopedPermission('app.projects.edit', $project);
```

Priority resolution: global wildcard `*` → global roles → scoped roles.

## Чеклист нового permission

1. case в enum guard-домена
2. метод политики + `#[GateAbility]` (или `#[RoleOnly]` для role-only)
3. `#[CheckPermission]` на контроллере
4. ключ в Abilities DTO (если UI)
5. resolved string в роли
6. `php artisan guard:doctor`
7. тест allow/deny

См. [recipes.md](guide/recipes.md), [filament.md](guide/filament.md), [entity-scopes.md](guide/entity-scopes.md).

## Документация (docs/guide)

| Файл | Описание |
|------|-------------|
| `why-azguard.md` | Мотивация, проблемы DB-first RBAC |
| `concept.md` | Концепция: панели → роли → права → политики |
| `comparison.md` | AzGuard vs Spatie vs Bouncer vs Laratrust |
| `architecture.md` | Структура пакета, service provider, authorizer flow |
| `getting-started.md` | Установка, первая роль |
| `entity-scopes.md` | Entity-scoped роли (Sprint 4) |
| `recipes.md` | Реальные паттерны |
