# Changelog

All notable changes to `axioma-studio/azguard-context` are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and
the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed
- `ContextGuard::checkInContext` no longer busts the durable cross-request
  permission cache on every check. It now uses the resolver's in-process-only
  `forgetRequestCache()` for its transient set/restore of the active context,
  instead of `forgetForUser()`, which (as of core F30) bumps the persistent
  per-user epoch — previously turning each context check into a full
  cross-request cache invalidation and unbounded epoch growth on a persistent
  store. (F30-fix)

## [0.1.0]

Initial release — opt-in multi-workspace / multi-site authorization context:
`AuthorizationContext` value object, the context manager and guard, and the
context-aware permission merge strategies.
