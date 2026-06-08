# AI Context — AzGuard

## Sprint Status

| Sprint | Branch | PR | Status |
|--------|--------|----|--------|
| Sprint 1 | `feat/sprint-1-core` | PR #1/#2 | ✅ Merged |
| Sprint 2 | `feat/sprint-2-tests` | [PR #3](https://github.com/axioma-studio/azguard-private/pull/3) | ⏳ Ready for Review |
| Sprint 3 | `feat/sprint-3-docs` | [PR #5](https://github.com/axioma-studio/azguard-private/pull/5) | ⏳ Ready for Review |
| Sprint 4 | `feat/sprint-4-entity-scopes` | [PR #4](https://github.com/axioma-studio/azguard-private/pull/4) | ⏳ Ready for Review |
| Sprint 5 | `feat/sprint-5-ci` | — | 🟡 In Progress |
| Direct Grants | `feature/permission-catalog-phase-1` | — | 🟡 In Progress |

---

## Что есть в пакете

- Gate, миграции, `Role`, `HasAzGuard`, `Panel`, `PanelProvider`
- `PolicyAttributeRegistrar`, `GateAbility`, `GuardPolicy`, `CheckPermission`, `CheckAccess`
- `AuthorizesPermission`, `SkipGuardCheck`, `RoleOnly`
- `resolvePermission`, `AzGuard::permission()`, `guard:doctor`
- Generators: `make:guard-panel`, `make:guard-permission`, `make:guard-policy`, `make:guard-abilities`, `make:guard-role`
- `AbilitiesDto`, `azguard/filament` (`GuardResource`)
- **Sprint 4**: `InteractsWithAzScopes` — entity-scoped roles API (`assignScopedRole`, `removeScopedRole`, `hasScopedRole`, `hasScopedPermission`)
- **v0.3**: Direct Grants — `HasDirectGrants`, `GrantBuilder`, `DirectGrantPolicy`, `@azdirect`, Artisan CLI

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

## Контракт Direct Grants (v0.3)

```php
// Установить трейт на User-модель
use HasAzGuard, HasDirectGrants;

// Fluent API
AzGuard::forUser($user)->on('app')->ttl(3600)->give('app.x');
AzGuard::forUser($user)->on('app')->revoke('app.x');

// Короткий хелпер
AzGuard::grantDirect($user, 'app.x', 'app', ttl: 3600);
AzGuard::revokeDirect($user, 'app.x', 'app');
AzGuard::activeGrants($user, 'app'); // Collection<DirectGrant>

// Проверка
$user->hasDirectGrant('app.x', 'app');             // bool
Gate::allows('direct-grant', 'app.x');             // bool
Gate::allows('direct-grant', ['app.x', 'app']);    // bool

// Blade
// @azdirect('app.x') ... @endazdirect

// Middleware
// ->middleware('az.grant:app.x,app')

// Artisan
// php artisan az-guard:grant {id} {perm} {panel} [--ttl=] [--model=]
// php artisan az-guard:revoke-grant {id} {perm} {panel} [--all] [--force]
// php artisan az-guard:prune-grants [--panel=]
```

`hasAzPermission()` автоматически проверяет роль **ИЛИ** direct grant. Остальной код менять не нужно.

## Чеклист нового permission

1. case в enum guard-домена
2. метод политики + `#[GateAbility]` (или `#[RoleOnly]` для role-only)
3. `#[CheckPermission]` на контроллере
4. ключ в Abilities DTO (если UI)
5. resolved string в роли
6. `php artisan guard:doctor`
7. тест allow/deny

См. [recipes/index.md](guide/recipes/index.md), [filament.md](guide/filament.md), [entity-scopes.md](guide/entity-scopes.md), [direct-grants.md](guide/direct-grants.md).

## Документация (docs/guide)

| Файл | Описание |
|------|-------------|
| `why-azguard.md` | Мотивация, проблемы DB-first RBAC |
| `concept.md` | Концепция: панели → роли → права → политики |
| `comparison.md` | AzGuard vs Spatie vs Bouncer vs Laratrust |
| `architecture.md` | Структура пакета, service provider, authorizer flow |
| `quick-start.md` | Установка, первая роль |
| `entity-scopes.md` | Entity-scoped роли (Sprint 4) |
| `direct-grants.md` | Direct Grants (v0.3) — все способы выдачи/проверки |
| `recipes/index.md` | Реальные паттерны |
