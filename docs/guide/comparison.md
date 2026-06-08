# AzGuard vs. Competitors

This page provides an honest, up-to-date comparison of AzGuard against the most
popular Laravel permission packages.

## Feature Matrix

| Feature | AzGuard | Spatie Permission | Bouncer | Laratrust |
|---|---|---|---|---|
| **Role definition** | PHP class (code-first) | DB record | DB record | DB record |
| **Permission definition** | String in PHP class | DB record | DB record | DB record |
| **Multi-panel / multi-guard** | ✅ Native | ⚠️ Manual | ❌ No | ⚠️ Partial |
| **Entity-scoped roles** | ✅ Sprint 4 | ❌ No | ✅ Yes | ❌ No |
| **Laravel Gate integration** | ✅ Native | ✅ Yes | ✅ Yes | ✅ Yes |
| **PHP 8.x Attributes** | ✅ Yes | ❌ No | ❌ No | ❌ No |
| **Wildcard permissions** | ✅ Configurable | ✅ Yes | ❌ No | ❌ No |
| **Filament v3 support** | ✅ Native package | ⚠️ Community plugin | ❌ No | ❌ No |
| **Cache strategy** | Per-user (store-agnostic) | Per-user (cache store) | Per-user | Per-user |
| **Octane / Kubernetes safe** | ✅ Stateless | ⚠️ Known issues | ✅ Yes | ⚠️ Untested |
| **Type-safe role logic** | ✅ Full PHP class | ❌ No | ❌ No | ❌ No |
| **Artisan commands** | sync-roles, cache-reset, doctor | cache-reset | — | — |
| **Pest test helpers** | ✅ Sprint 2 | ❌ No | ❌ No | ❌ No |

## Why not Spatie?

Spatie Permission is the most widely used package, but it has structural limitations
that become painful at scale:

- **Cache bloat** — storing all permissions per user in a single cache key grows
  rapidly with 100+ permissions (4+ MB per user observed in production).
- **DB-first** — permissions live in the database, making code review, version
  control, and static analysis impossible.
- **No multi-panel** — there is no built-in concept of isolated permission namespaces
  per application panel or guard.
- **Octane issues** — shared state between requests causes permission bleed in
  long-running processes.

## Why not Bouncer?

Bouncer’s entity-scoped roles (`$bouncer->allow($user)->to('edit', $post)`) are
powerful, but the package uses its own separate Gate clipper that can conflict with
custom Gate hooks, and it does not support multi-panel architectures.

## AzGuard’s unique strengths

1. **Code-first roles** — roles are PHP classes, fully type-safe, diffable, testable.
2. **Panel namespacing** — `app.posts.edit` and `admin.posts.edit` are isolated by design.
3. **PHP Attributes** — `#[CheckPermission]`, `#[RoleOnly]`, `#[SkipGuardCheck]` on
   controllers for declarative access control.
4. **Filament-native** — `azguard/filament` provides `GuardResource` out of the box.
5. **Octane-safe** — no static state, per-request cache keyed by user ID.
