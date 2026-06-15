# Changelog

All notable changes to `axioma-studio/azguard-filament` are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and
the project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.0.0]

### Added

- Filament v5 admin UI for AzGuard.
- `RoleResource` (with permission and user relation managers) and
  `DirectGrantResource` for managing roles and direct grants.
- `AzGuardPlugin` to register the integration on a Filament panel.
- Config-driven, zero-boilerplate resource permission enforcement via a Gate
  layer (`ResourceGate`), with database / enum / policy permission sources.
- `guard:filament:generate` command to scaffold permission enums or policies
  from Filament resources.

### Fixed

- Editing a role's permissions in the UI now flushes the cached permissions of
  every user holding that role.
