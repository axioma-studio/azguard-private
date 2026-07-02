# Changelog

All notable changes to `axioma-studio/azguard-filament` are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and
the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Opt-in page/widget access enforcement (F13).** New `HasAzGuardPage` and
  `HasAzGuardWidget` traits override Filament's `canAccess()` / `canView()`
  static hooks to consult the same catalogued `{panel}.{page|widget}.view`
  permission via a new read-only `PageWidgetAccessEvaluator`. Filament routes
  custom Pages through `canAccess()` and Widgets through `canView()` — neither
  goes through the Gate, so `ResourceGate` structurally could not reach them;
  previously the catalogued permission was cosmetic and hiding a nav link only
  hid the link while the page stayed reachable by direct URL. Enforced on every
  mount and Livewire round-trip, not just navigation rendering. Degrades to
  "allow" when no AzGuard-linked panel/user is resolvable, mirroring
  `ResourceGate`'s `enforce`/`source` escape hatches. `FilamentDiscovery` now
  also discovers widgets, so the existing `widgets.ability` / `widgets.exclude`
  config keys actually produce catalogued subjects. (Nav-hiding is not access
  control.)

### Changed
- **`guard:filament:generate` enum codegen now honours the schema (F11).** The
  `PermissionEnumGenerator` respects the configured key case/format from
  `PermissionSchema` so generated enum cases round-trip the exact runtime
  permission key instead of a divergent hardcoded casing.
- **`AzGuardPlugin` default panel id is read from config (F39).** The fallback
  panel id now comes from `config('az-guard.default_panel')` instead of a
  hardcoded literal, so a plugin registered without an explicit id targets the
  app's configured default panel.
- Added the `user_label_column` config key to `az-guard-filament.php` (F41). It
  controls which user-model column labels users in the DirectGrant and Role UIs
  (defaults to `name`, override to `email` or any column). The key was
  previously phantom — read from config with no definition — falling back to
  `name` in four read sites.

### Performance
- **DoctorPage no longer recomputes diagnostics per render hook (F29).**
  `AzGuardDiagnostics::diagnose()` ran up to 3× per render (nav badge, badge
  colour, view data); it is now memoized once per request via an Octane-safe
  `RequestState::remember()`. `DirectGrantResource`'s user-label column no
  longer N+1s (a `find()` per row) — the `grantable` morphTo relation is
  eager-loaded via `modifyQueryUsing()`.

## [0.1.0]

Initial release — Filament admin UI for AzGuard: role and direct-grant
resources, config-driven resource permission enforcement, the Doctor page, and
the `guard:filament:generate` command.
