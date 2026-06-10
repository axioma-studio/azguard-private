# azguard-compatibility

Use when reviewing code, dependencies, or CI for PHP/Laravel version compatibility.

## Support Matrix

| Package | PHP | Laravel | Testbench |
|---|---|---|---|
| core | ^8.3 | ^11.0 \| ^12.0 | ^9.0 \| ^10.0 |
| filament | ^8.2 | — | Filament ^4.0 \| ^5.0 |
| context | ^8.3 | ^11.0 \| ^12.0 | ^9.0 \| ^10.0 |

## Workflow

1. Check `composer.json` constraints in the affected package.
2. Verify that new PHP syntax does not exceed 8.3 (no 8.4+ features unless updating support matrix).
3. Verify that new Laravel APIs exist in both Laravel 11 and 12.
4. Check `.github/workflows/tests.yml` — CI runs PHP 8.3 and 8.4 against Laravel 11.x and 12.x.
5. When updating a constraint, ensure all three packages stay consistent.

## Common Pitfalls

- `readonly` class properties require PHP 8.1+, `readonly` classes require PHP 8.2+ — both are safe.
- First-class callable syntax `Closure::fromCallable(...)` → `$fn(...)` requires PHP 8.1+ — safe.
- `array_is_list()` requires PHP 8.1+ — safe.
- Laravel 12 removed several deprecated methods — check against both versions.
- Filament 5 requires PHP 8.2+, which is why `packages/filament` has `^8.2` not `^8.3`.

## Do Not

- Use local `vendor/` as proof of compatibility — check version constraints.
- Add PHP 8.4+ syntax without updating the support matrix and CI.
- Ignore Windows path separators if adding shell scripts to CI.
