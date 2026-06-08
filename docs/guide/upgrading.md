# Upgrading

## From v0.x to v1.0

Version 1.0 introduces several breaking changes to method names and the trait API.

### Method renames on `HasAzGuard`

| v0.x | v1.0 |
|---|---|
| `hasAzPermission()` | `hasPermission()` |
| `giveAzPermission()` | Use `HasDirectGrants::directGrant()` |
| `revokeAzPermission()` | Use `HasDirectGrants::revokeDirectGrant()` |
| `clearAzPermissionsCache()` | `flushPermissions()` |

### Search and replace

Run these in your project root:

```bash
# hasAzPermission → hasPermission
grep -r 'hasAzPermission' . --include='*.php'

# giveAzPermission → use GrantBuilder instead
grep -r 'giveAzPermission' . --include='*.php'

# clearAzPermissionsCache → flushPermissions
grep -r 'clearAzPermissionsCache' . --include='*.php'
```

### Config changes

No config keys were renamed in v1.0. Your existing `config/az-guard.php` is compatible.

### Migration changes

No new migrations in v1.0. If you published migrations in v0.x, they remain valid.

### Panel provider interface

No changes. Existing panel providers work without modification.

## From Spatie Permission

If you are migrating from Spatie's `laravel-permission`, see the [Comparison page](/guide/comparison) for a feature mapping and the recipes section for migration patterns.
