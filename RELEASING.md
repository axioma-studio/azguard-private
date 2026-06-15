# Releasing AzGuard

AzGuard is developed as a monorepo and published as three standalone Composer
packages:

| Package | Path | Packagist |
| --- | --- | --- |
| `axioma-studio/azguard-core` | `packages/core` | core RBAC engine |
| `axioma-studio/azguard-filament` | `packages/filament` | Filament admin UI |
| `axioma-studio/azguard-context` | `packages/context` | multi-workspace context |

## Versioning: lockstep

All three packages share **one version**. A release tags the monorepo and
splits the same tag into every package repo, so `azguard-core`,
`azguard-filament` and `azguard-context` always advance together.

Consequences:

- Satellites require the core at the **same major**: `"axioma-studio/azguard-core": "^1.0"`.
- A breaking change in any package bumps the major for all three.
- Never tag a single package independently — always tag the monorepo.

The package `composer.json` files intentionally carry **no `version` field**:
Composer derives the version from the git tag the split action pushes.

## Cutting a release

1. Ensure `main` is green: `composer test`, `composer analyse`, `composer lint:check`, `composer refactor:check`.
2. Update `CHANGELOG.md` (or let `git-cliff` generate it on tag).
3. Tag and push:
   ```bash
   git tag v1.0.0
   git push origin v1.0.0
   ```
4. CI takes over:
   - **`release.yml`** validates every package manifest (`composer validate --strict`),
     re-runs analyse + style + tests, then creates the GitHub release.
   - **`split.yml`** pushes `packages/*` to the standalone repos with the same tag.
   - Packagist picks up each new tag via webhook.

Local resolution of the `^1.0` constraints between packages is handled by the
`versions` map in the root `composer.json` path repository — keep it in sync with
the current major when bumping (e.g. `2.0.0` after a `v2.0.0`).

## One-time setup (before the first split)

1. Create three empty repos under the org: `azguard-core`, `azguard-filament`,
   `azguard-context`. They are **read-only mirrors** — never push to them by hand.
2. Create a fine-grained PAT (or a deploy setup) with push access to all three and
   add it as the `MONOREPO_SPLIT_TOKEN` repository secret.
3. Submit each mirror to [Packagist](https://packagist.org/packages/submit) and
   enable the GitHub service hook so new tags auto-update.
