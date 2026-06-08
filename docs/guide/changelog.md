# Changelog

All notable changes are documented here. AzGuard follows [Semantic Versioning](https://semver.org/).

## 0.x (dev)

> Pre-release development. API is stable within each sprint but may have breaking changes between sprints until `1.0.0`.

### Sprint 9 — Documentation
- Restructured docs into Introduction / Core Concepts / Features / Integrations / Reference / Recipes
- New pages: `quick-start`, `panels`, `permission-catalog`, `artisan-commands`, `configuration`
- Rewrote: `roles`, `permissions`, `policies-and-gates`, `http-access`, `abilities-frontend`, `comparison`, `filament`, `why-azguard`, `entity-scopes`
- Recipes split into standalone pages: soft-role-override, super-admin-wildcard, temp-access-via-grant

### Sprint 8 — CI / Quality
- Pint + PHPStan level 8 enforced in CI
- `UserWithDirectGrants` stub refactored
- Test suite: 7 files, alignment fixes

### Sprint 7 — Custom Roles
- DB-backed `AzRole` model for runtime role creation
- `RoleResource` in Filament: create/edit/delete custom roles, permission picker
- `assignRole($azRoleInstance)` support in `HasAzGuard`

### Sprint 6 — Direct Grants
- `AzDirectGrant` model with `expires_at` and `revoked_at`
- `DirectGrantResource` in Filament
- `giveAzPermission()` / `revokeAzPermission()` on User
- `artisan azguard:grant` command

### Sprint 5 — Filament Integration
- `azguard/filament` package released
- `AzGuardPlugin`, `AzGuardResource` base class
- `forPanel()` panel binding

### Sprint 4 — Entity Scopes
- `InteractsWithAzScopes` trait
- `assignScopedRole()` / `removeScopedRole()` / `hasScopedRole()`
- `hasScopedPermission()` with wildcard → global → scoped resolution order

### Sprint 3 — Context
- Opt-in `AzGuardContext` for tenant/team-aware permission checks
- `withContext()` binding, zero overhead when unused

### Sprint 2 — Testing Helpers
- Pest helpers: `actingAsWithRole()`, `assertCanAzPermission()`, `assertCannotAzPermission()`
- `UserWithDirectGrants` test stub

### Sprint 1 — Core
- `HasAzGuard` trait
- Panel system with `PanelProviderInterface`
- `#[GateAbility]`, `#[CheckPermission]`, `#[RoleOnly]`, `#[SkipGuardCheck]` attributes
- `Gate::before` wildcard integration
- `azguard:doctor`, `azguard:sync-roles`, `azguard:cache-reset`
