# AzGuard

Role-based access control (RBAC) and permission system for Laravel. Use Laravel-native APIs (Gate, policies, middleware) and existing service structures before adding new abstractions.

## Architecture

Monorepo with three packages:

- **`packages/core/`** (`AzGuard\`) — core roles, permissions, direct grants, Panel system
- **`packages/filament/`** (`AzGuard\Filament\`) — Filament admin UI (roles, grants management)
- **`packages/context/`** (`AzGuard\Context\`) — multi-workspace/multi-site authorization context

Tests live in `tests/` at the repo root and cover all three packages.

## Quick Commands

- Run tests: `composer test`
- Tests with coverage: `composer test:coverage`
- Unit tests only: `composer test:unit`
- Feature tests only: `composer test:feature`
- Lint (fix): `composer lint`
- Lint (check only): `composer lint:check`
- Static analysis: `composer analyse`
- Refactor (apply): `composer refactor`
- Refactor (dry-run): `composer refactor:check`
- Mutation testing: `composer mutate`

## Code Conventions

- `declare(strict_types=1)` in every PHP file
- PHPStan level 6 via Larastan
- Pest 4 for all tests — no PHPUnit syntax
- Rector for automated refactoring (PHP 8.3 target)
- Laravel Pint for code style (Laravel preset + stricter rules)
- Contracts are always interfaces in `Contracts/` namespace
- Models extend `Illuminate\Database\Eloquent\Model`
- Commands extend `Illuminate\Console\Command`
- No `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()` in source

## Key Concepts

- **Panel** — authorization scope/context (e.g. admin, tenant). Set via `SetCurrentPanel` middleware or `azguard.panel_check`.
- **GrantSource** — pluggable permission resolver (`ClassRoleGrantSource`, `DatabaseRoleGrantSource`, `DirectGrantSource`).
- **PermissionCatalog** — registry of all declared permissions, built from enums or policy attributes.
- **PanelManager** — registers and resolves Panel instances.
- **AzGuardManager** — main facade entry point for permission checks.

## Local Skills

- `azguard-testing` — adding or changing Pest tests with Orchestra Testbench
- `azguard-scaffold` — new class or component in core/filament/context
- `azguard-compatibility` — PHP/Laravel version compatibility checks
- `azguard-docs` — README, VitePress docs, CHANGELOG
- `azguard-release` — tags, changelog, GitHub release workflow
- `azguard-permissions` — working with Panel, GrantSource, PermissionCatalog
