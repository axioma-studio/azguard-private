# Changelog

All notable changes to `axioma-studio/azguard-core` are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and
the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Security
- **Entity-scoped roles are now isolated per panel (F8).** A new migration adds a
  nullable `panel_id` to `model_has_scopes`; assignments persist it and permission
  checks filter by it (`null` = any panel, for back-compat). Previously a scope —
  including a scoped `*` — assigned under one panel was honored in every panel, a
  breach of the core isolation boundary. *Known limitation:* the `HasScopedRoles`
  Eloquent global query-scope is still not panel-aware (it filters by `scope_class`
  regardless of `panel_id`), so scoped *query filtering* can still cross panels even
  though scoped permission *checks* are now isolated — tracked for a follow-up.

### Fixed
- `guard:grants` now queries the `grantable_type` / `grantable_id` columns that the
  `direct_grants` migration actually creates (previously `model_type` / `model_id`,
  so the command returned nothing / errored). The suppressing `phpstan-baseline`
  entries were removed rather than kept. (F1)
- `AbilitiesDto::toArray()` no longer leaks non-boolean subclass properties — it
  returns only the resolved boolean ability map. (F4)
- Enum-based scoped roles now actually grant. `hasScopedPermission` resolves a
  role's declared enum cases (`list<UnitEnum>`, the documented preferred form)
  through the panel — via the single `ClassRoleGrantSource` resolution seam —
  before matching, instead of comparing a resolved string against raw enum cases
  and silently denying. (F3)
- The `AzGuard::grant` / `revoke` / `grants` facade shorthands now default
  `panelId` to `null` and resolve through `config('az-guard.default_panel')`
  (were hardcoded to `'app'`, so an app with a non-`app` default panel wrote
  grants to the wrong panel). `@method` signatures updated to `?string $panelId`. (F6)
- `DirectGrantSource` now honors `features.direct_grants` (disabled ⇒ no grants,
  reads stopped too — not just writes) and the configured `direct_grant` model
  (was hardcoded on the hottest read path). (F17)
- The policy ability-catalog builder no longer crashes application boot on a
  stale/renamed policy class — a `class_exists()` guard skips it and surfaces a
  warning to the log instead of throwing an uncaught `ReflectionException` while
  building the boot-time catalog. Both catalog builders now receive the manager
  via DI, matching the grant-source direction. (F27)
- Dynamic permission definitions (e.g. `app.team.{id}.admin`) are now honored by
  the resolver: `PermissionDefinition::isDynamic()` is read and `{seg}` placeholder
  segments match a single concrete segment, so entity-scoped grants are no longer
  dropped as unknown. Non-dynamic keys still require an exact catalog match. (F28)
- The permission cache key now embeds a per-user epoch that is bumped on
  `forgetForUser`, so a role/grant change invalidates every context-discriminated
  set at once — previously only the base key was dropped, leaving contextual sets
  stale under a persistent store with infinite TTL. A transient context switch uses
  the new in-process-only invalidation (below) and no longer busts the durable,
  cross-request cache. (F30)

### Added
- `AbilitiesDto::make(...)` — the supported way to instantiate an abilities DTO:
  resolves each ability against the Gate and spreads the result into the subclass
  constructor as named arguments. The generated stub was updated to match. (F2)
- `scope_class` is now nullable; logic-less scoped roles store `null` instead of a
  fragile anonymous-class-name sentinel that could not be reliably re-instantiated.
  A new migration relaxes the column. (F48)
- `PermissionResolverInterface::forgetRequestCache()` — in-process-only cache
  invalidation that drops the current request's resolved sets **without** bumping
  the durable per-user epoch; used for transient authorization-context switches. (F30)

### Added
- `AbilitiesDto::make(...)` — the supported way to instantiate an abilities DTO:
  resolves each ability against the Gate and spreads the result into the subclass
  constructor as named arguments. The generated stub was updated to match. (F2)

### Removed
- **Breaking:** the unregistered, unreachable `guard:revoke` command
  (`RevokeCommand`, a raw-column duplicate) was deleted. Use the
  production-wired `guard:revoke-grant` (`RevokeGrantCommand`), which revokes
  through `GrantBuilder`. Note: `--all` on `guard:revoke-grant` is scoped to a
  single panel (the required `panel` argument), by design.

## [0.1.0]

Initial release — code-first, enum/class-first RBAC for Laravel: panels, enum/
class permissions, code roles, direct grants, pluggable GrantSources, permission
cache, and the `azguard:*` / `make:guard-*` console commands.
