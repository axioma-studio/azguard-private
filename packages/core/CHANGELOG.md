# Changelog

All notable changes to `axioma-studio/azguard-core` are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and
the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed
- `guard:grants` now queries the `grantable_type` / `grantable_id` columns that the
  `direct_grants` migration actually creates (previously `model_type` / `model_id`,
  so the command returned nothing / errored). The suppressing `phpstan-baseline`
  entries were removed rather than kept. (F1)
- `AbilitiesDto::toArray()` no longer leaks non-boolean subclass properties — it
  returns only the resolved boolean ability map. (F4)

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
