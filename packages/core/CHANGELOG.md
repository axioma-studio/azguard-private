# Changelog

## 0.3.0

### Direct Grants

- `Models/DirectGrant` — Eloquent-модель с `scopeActive()`, `scopeForPanel()`, полиморфной связью `grantable`
- Миграция `az_direct_grants` с unique-индексом
- `Concerns/HasDirectGrants` — трейт: `hasDirectGrant()`, `activeDirectGrants()`, переопределение `hasAzPermission()`
- `Grants/GrantBuilder` — fluent API: `on()`, `ttl()`, `give()`, `revoke()`, `revokeAll()`, `list()`
- `Events/GrantGiven`, `Events/GrantRevoked`
- `AzGuardManager::forUser()`, `grantDirect()`, `revokeDirect()`, `activeGrants()`
- `Auth/DirectGrantPolicy` — Gate-политика `'direct-grant'`
- `@azdirect` / `@endazdirect` Blade-директива
- Middleware-алиас `az.grant` → `CheckDirectGrant`
- Artisan: `az-guard:grant`, `az-guard:revoke-grant`, `az-guard:prune-grants`
- Конфиг: `table_names.direct_grants`, `models.direct_grant`, `features.direct_grants`
- Документация: `docs/guide/direct-grants.md`

## 0.2.0

- `Gate::before` через `Authorizer`
- Контракт роли: `name` = slug, `class_name` = FQCN класса роли
- `hasAzPermission` поддерживает `*`
- `PermissionName`, middleware `azguard.roles`
- `PanelProvider` регистрирует abilities из `Permissions/*Permission::map()`
- Laravel 13 в `illuminate/*`
