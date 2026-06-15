# Changelog

All notable changes to `axioma-studio/azguard-context` are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and
the project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.0.0]

### Added

- Multi-workspace / multi-site authorization context for AzGuard (opt-in package).
- `AuthorizationContext` value object and request-scoped `AuthorizationContextManager`.
- Context-scoped roles backed by the `az_guard_context_roles` table
  (morph key type follows `az-guard.column_names.morph_type`).
- Pluggable `MergeStrategy` with three built-ins: `GlobalPlusContextStrategy`
  (default — global ∪ context), `ContextOnlyStrategy` (context only),
  `DenyWithoutContextStrategy` (no active context ⇒ deny).
- `ContextPermissionLayer` (a core `PermissionLayer`) applies the strategy after
  the global permission set is resolved, so context-only and deny strategies can
  restrict access, not only add to it.
- `ContextGuard` for one-off contextual checks (`$user->hasPermissionIn(...)`).
- `SetAuthorizationContext` middleware and configurable `ResolvesContext` resolvers.

### Security

- Context and its dependents are bound `scoped`, so one tenant's context cannot
  leak into another request on a reused Octane worker.
- The permission cache key includes the active context, so two workspaces on the
  same panel never share a cached permission set.
