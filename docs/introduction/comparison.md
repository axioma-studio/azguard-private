# AzGuard vs Competitors

An honest, up-to-date comparison of AzGuard against the most popular Laravel permission packages.

## Feature matrix

| Feature | AzGuard | Spatie Permission | Bouncer | Laratrust |
|---|---|---|---|---|
| **Role definition** | PHP class (Git) | DB record | DB record | DB record |
| **Permission definition** | Enum case (Git) | DB record | DB record | DB record |
| **Custom (runtime) roles** | ✅ DB-backed `Role` | ✅ Yes | ✅ Yes | ✅ Yes |
| **Direct grants** | ✅ Per-user, per-panel | ✅ Yes | ✅ Yes | ✅ Yes |
| **Multi-panel / namespacing** | ✅ Native | ⚠️ Manual | ❌ No | ⚠️ Partial |
| **Entity-scoped roles** | ✅ Sprint 4 | ❌ No | ✅ Yes | ❌ No |
| **Laravel Gate native** | ✅ `Gate::before` + define | ✅ Yes | ✅ Yes | ✅ Yes |
| **PHP 8.x Attributes** | ✅ `#[CheckPermission]` | ❌ No | ❌ No | ❌ No |
| **Wildcard permissions** | ✅ Configurable `*` | ✅ Yes | ❌ No | ❌ No |
| **Filament v5 support** | ✅ First-party package | ⚠️ Community plugin | ❌ No | ❌ No |
| **Octane / K8s safe** | ✅ Stateless | ⚠️ Known issues | ✅ Yes | ⚠️ Untested |
| **Type-safe roles** | ✅ Full PHP class | ❌ Strings only | ❌ Strings only | ❌ Strings only |
| **Built-in doctor** | ✅ `guard:doctor` | ❌ No | ❌ No | ❌ No |

## Why not Spatie?

Spatie Permission is the most widely used package, but it has structural limitations that become painful at scale:

- **DB-first** — permissions live in the database, making code review, version control, and static analysis impossible without extra tooling.
- **No multi-panel** — there is no built-in concept of isolated permission namespaces per application section or guard.
- **Cache bloat** — all permissions per user are stored in a single cache key. With 100+ permissions, this can reach 4 MB per user in production.
- **Octane issues** — shared state between requests causes permission bleed in long-running processes.

## Why not Bouncer?

Bouncer's entity-scoped roles (`$bouncer->allow($user)->to('edit', $post)`) are powerful, but the package uses its own Gate clipper that can conflict with custom `Gate::before` hooks, and it does not support multi-panel architectures.

## AzGuard's unique strengths

1. **Code-first** — roles are PHP classes, fully type-safe, diffable, testable in Git.
2. **Panel namespacing** — `app.posts.edit` and `admin.posts.edit` are isolated by design.
3. **PHP Attributes** — `#[CheckPermission]`, `#[RoleOnly]`, `#[SkipGuardCheck]` on controllers for declarative access control.
4. **Filament-native** — `axioma-studio/azguard-filament` provides config-driven, zero-boilerplate authorization plus `RoleResource` and `DirectGrantResource` out of the box.
5. **Octane-safe** — no static state, per-request cache keyed by user ID.
6. **Doctor command** — built-in `guard:doctor` catches misconfigurations before they reach production.
