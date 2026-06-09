# AzGuard — Code Quality Refactoring Plan

> Tracking document for [Issue #38](https://github.com/axioma-studio/azguard-private/issues/38).
> Branch: `refactor/code-quality-plan-38`

---

## 1. Duplication & API Inconsistency

### 1.1 — Parallel permission-check API

- [ ] Remove `hasAzPermission()` from `HasDirectGrants` — logic already covered by `DirectGrantSource` in the resolver
- [ ] Rename `checkPermission()` → `canPermission()` or add `bool $throw = false` to `hasPermission()`
- [ ] Update docs and tests

### 1.2 — panelId resolution duplicated in 3 places

- [ ] Extract to `Support\PanelIdResolver::resolve(?string $panelId): string` with exception on null
- [ ] Apply in `HasDirectGrants`, `GrantBuilder`, and any other callers

### 1.3 — `AzGuardManager` thin wrappers over `GrantBuilder`

- [ ] Consider deprecating `grantDirect()` / `revokeDirect()` / `activeGrants()` in favour of fluent API
- [ ] Or document them explicitly as short-hands with `@see GrantBuilder`

### 1.4 — `expires_at` filter duplicated in `DirectGrant` scope AND `DirectGrantSource`

- [ ] Replace raw `DB::table()` query in `DirectGrantSource` with `DirectGrant::query()->active()->forPanel()`
- [ ] Single source of truth: `scopeActive()`

### 1.5 — `table_names` via `config()` directly in Sources

- [ ] Replace `config('az-guard.table_names.*')` calls with `Config::*Table()` helpers in all Sources

---

## 2. Performance & Caching

### 2.1 — `syncRoles()`: N+1 cache flushes

- [ ] Use `$this->roles()->sync($roleIds)` instead of detach/attach loop
- [ ] Fire `RoleAttached` / `RoleDetached` only for actually changed roles

### 2.2 — `EffectivePermissionResolver::resolve()` — sort on every call

- [ ] Cache sorted sources list in `__construct()` or via `once()`

### 2.3 — `PermissionResolverCache::retryLoad()` — `usleep` in sync context

- [ ] Add doc comment: behaviour is only relevant under Octane (concurrent coroutines)
- [ ] Consider dedicated Octane adapter or at minimum annotate with `@octane-safe`

### 2.4 — `assignRole()` / `removeRole()` — `flushPermissions()` inside loop

- [ ] Move `flushPermissions()` and `unsetRelation('roles')` outside the iteration, call once after

### 2.5 — `ClassRoleGrantSource` — `class_uses_recursive` on every call

- [ ] Cache via `static` variable or `once()`
- [ ] Or replace reflection with `$user instanceof HasAzGuardInterface` after interface is introduced

### 2.6 — `PermissionSet::matchesWildcard()` — regex compiled on every invocation

- [ ] Pre-compile wildcard regex patterns in `__construct()` and store as `$wildcardPatterns`

---

## 3. Naming

### 3.1 — Inconsistent middleware aliases

- [ ] Rename `az.grant` → `azguard.grant` (add deprecation alias)

### 3.2 — Misleading class names

- [ ] `GuardDoctor` → `AzGuardDiagnostics`
- [ ] `DiscoveryService` → `PanelDiscovery`

### 3.3 — `PanelManager` in `Guard/` — empty proxy

- [ ] Audit content, move to `Support/` or remove

### 3.4 — `AzGuardContextBridge` — wrong pattern name

- [ ] Rename → `ContextPackageProxy` or `OptionalContextResolver`

### 3.5 — `DirectGrant` model not `final`

- [ ] Add `final` if custom model via config is not intended, otherwise document extension point

---

## 4. Code Quality & Syntax

### 4.1 — Russian strings in production exception messages

- [ ] Translate all exception messages to English
- [ ] Create domain exceptions: `PanelNotFoundException`, `PanelNotSetException`

### 4.2 — `PermissionSet::keys()` === `PermissionSet::toArray()`

- [ ] Remove one, mark the other `@deprecated` with redirect

### 4.3 — `scopes()` in `HasAzGuard` missing return type

- [ ] Add `MorphMany` return type
- [ ] Run PHPStan level 8 across `packages/core/src`

### 4.4 — `Gate::before()` overly broad `method_exists` check

- [ ] Replace with `$user instanceof HasAzGuardInterface`

### 4.5 — Blade directives: redundant `auth()->check()`

- [ ] Make `hasPermission()` / `hasRole()` guest-safe, remove guard from Blade templates

### 4.6 — `CheckAccess::getPermissionAttributes()` — Reflection on every request

- [ ] Cache `ReflectionMethod` + attributes by `"$controller@$method"` key via `once()` or `static`

---

## 5. Laravel Package Best Practices

### 5.1 — Add `spatie/laravel-package-tools`

- [ ] Add to `composer.json`
- [ ] Refactor `AzGuardServiceProvider` to extend `PackageServiceProvider`

### 5.2 — Add `pestphp/pest`

- [ ] Check existing test suite
- [ ] Plan migration to Pest
- [ ] Cover: `GrantBuilder`, `HasAzGuard`, `EffectivePermissionResolver`, `PermissionSet`

### 5.3 — Add PHPStan / Larastan to CI

- [ ] `composer require --dev nunomaduro/larastan`
- [ ] Configure `phpstan.neon` at level 6 (target: level 8)
- [ ] Add to GitHub Actions workflow

### 5.4 — Add `rector/rector`

- [ ] Config: `LevelSetList::UP_TO_PHP_83` + `LaravelSetList::LARAVEL_110`
- [ ] Run, review, commit in batches

### 5.5 — Introduce `HasAzGuardInterface`

- [ ] Create `Contracts/HasAzGuardInterface` with `hasPermission()`, `hasRole()`, `permissionSet()`
- [ ] `HasAzGuard` trait implements it by default
- [ ] Replace all `class_uses_recursive` / `method_exists` checks with `instanceof`

### 5.6 — `Config::class` — consider DTO replacement

- [ ] Create `AzGuardConfig` Value Object
- [ ] Register as singleton in container
- [ ] Gradually replace `Config::method()` static calls with DI

---

## Priority Matrix

| Priority | Task | Effort |
|---|---|---|
| 🔴 High | 4.1 Translate exception messages | 30 min |
| 🔴 High | 1.4 Deduplicate `expires_at` logic | 30 min |
| 🔴 High | 1.2 Extract `PanelIdResolver` | 1 h |
| 🔴 High | 1.5 Use `Config::*Table()` in Sources | 30 min |
| 🟡 Medium | 2.1 `syncRoles` N+1 | 1 h |
| 🟡 Medium | 2.4 `flushPermissions` outside loop | 30 min |
| 🟡 Medium | 2.2 Cache sorted grant sources | 30 min |
| 🟡 Medium | 2.5 Cache `class_uses_recursive` | 30 min |
| 🟡 Medium | 2.6 Cache wildcard regex in PermissionSet | 30 min |
| 🟡 Medium | 4.6 Cache Reflection in CheckAccess | 1 h |
| 🟡 Medium | 3.1 Unify middleware aliases | 30 min |
| 🟡 Medium | 1.1 Remove `hasAzPermission()` | 1 h |
| 🟢 Low | 5.5 `HasAzGuardInterface` | 2 h |
| 🟢 Low | 5.1 `spatie/laravel-package-tools` | 3-4 h |
| 🟢 Low | 5.3 PHPStan/Larastan level 6 | 2 h |
| 🟢 Low | 5.4 Rector | 2 h |
| 🟢 Low | 4.2 Deduplicate `keys()`/`toArray()` | 15 min |

---

## Commit Convention

```
refactor(core): extract PanelIdResolver helper
fix(core): translate all exception messages to English
fix(core): use scopeActive() in DirectGrantSource
perf(core): cache sorted grant sources in EffectivePermissionResolver
perf(core): cache ReflectionMethod attributes in CheckAccess middleware
perf(core): move flushPermissions() outside assignRole/removeRole loop
perf(core): pre-compile wildcard regex patterns in PermissionSet
refactor(core): replace class_uses_recursive with HasAzGuardInterface
chore(core): add larastan at level 6
chore(core): add rector with PHP 8.3 + Laravel 11 rules
```
