# Changelog

All notable changes to AzGuard are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and
the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0]

Initial public release — code-first, enum/class-first RBAC for Laravel.

### Added

- **Panels** — isolated authorization scopes, scaffolded with `make:guard-panel`
  (auto-registers the panel and wires its permission enum).
- **Enum/class-first API** — permissions as backed enums or `Permission` classes;
  roles as PHP classes declaring enum-case permissions; `assignRole(Role::class)`,
  `hasRole(Role::class)`, `hasPermission(Permission::Case)`, and `string|BackedEnum`
  panel identifiers.
- **Direct grants** — per-user permissions with an optional TTL.
- **Pluggable GrantSources** and a per-request/cross-request permission cache.
- **Console** — `azguard:install`, `azguard:super-admin`, `guard:doctor`,
  `guard:sync-roles`, plus `php artisan about` integration.
- **`axioma-studio/azguard-filament`** — Filament admin UI for roles and grants.
- **`axioma-studio/azguard-context`** — multi-workspace / multi-site authorization
  context (opt-in).
