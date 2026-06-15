# Changelog

All notable changes to `axioma-studio/azguard-core` are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and
the project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.0.0]

### Added

- Code-first RBAC for Laravel: roles, permissions, direct grants and panels.
- `HasAzGuard` trait (composing `HasRoles`, `HasPermissions`, `HasDirectGrants`)
  and `HasScopedRoles` for entity-scoped roles.
- Authorization through the Laravel Gate (`Gate::allows`, `$user->can`) and
  `$user->hasPermission(string|UnitEnum $permission, ?string $panelId = null)`,
  which accepts a key string or a permission enum.
- Pluggable grant sources (`GrantSource`, `priority(): int`): `ClassRole`,
  `DatabaseRole`, `DirectGrant`. Register custom sources with
  `AzGuard::registerGrantSource()`.
- Optional `PermissionLayer` hook applied after the global set is resolved.
- Typed `PermissionCatalog` built from permission enums and policy attributes.
- Panels via `PanelProvider` and the fluent `Panel` builder; configurable
  `az-guard.default_panel`.
- Direct grants with TTL/expiry and a fluent `GrantBuilder`.
- Events `RoleAttached`, `RoleDetached`, `GrantGiven`, `GrantRevoked` — the
  permission cache is flushed automatically when they fire.
- Artisan: `make:guard-*` generators and `guard:*` runtime commands.
- ULID/UUID morph-key support via `az-guard.column_names.morph_type`.

### Security

- Per-request services (`PermissionCache`, resolver) are bound `scoped` for
  Octane safety — no permission bleed between requests on a reused worker.
- Fail-closed authorization; with no active panel and several registered panels
  the Authorizer denies rather than evaluating against an arbitrary panel.
