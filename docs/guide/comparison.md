# Comparison with Alternatives

AzGuard is not the first Laravel RBAC package. Here's an honest comparison with the most popular alternatives.

## Feature Matrix

| Feature | AzGuard | Spatie Permission | Bouncer | Laratrust |
|---------|---------|-------------------|---------|----------|
| **Permissions stored in** | PHP Enums (code) | Database | Database | Database |
| **Roles stored in** | PHP classes + DB ref | Database | Database | Database |
| **IDE autocompletion** | ✅ Full (Enum) | ❌ Magic strings | ❌ Magic strings | ❌ Magic strings |
| **PHPStan support** | ✅ Native | ⚠️ Partial | ⚠️ Partial | ❌ None |
| **Git-trackable permissions** | ✅ Yes | ❌ No (DB only) | ❌ No (DB only) | ❌ No (DB only) |
| **Multi-panel scoping** | ✅ Built-in | ❌ No | ❌ No | ⚠️ Teams only |
| **PHP Attributes** | ✅ `#[CheckPermission]` | ❌ No | ❌ No | ❌ No |
| **Laravel Gate integration** | ✅ Native | ✅ Native | ✅ Native | ✅ Native |
| **Blade directives** | ✅ `@azcan`, `@azrole` | ✅ `@can`, `@role` | ✅ `@can`, `@ability` | ✅ Yes |
| **Wildcard permissions** | ✅ `app.*` | ✅ `posts.*` | ❌ No | ❌ No |
| **Direct permissions on user** | ❌ Roles only | ✅ Yes | ✅ Yes | ✅ Yes |
| **Octane / Kubernetes safe** | ✅ Redis cache | ⚠️ Known issues | ✅ Yes | ✅ Yes |
| **Artisan doctor/lint** | ✅ `azguard:doctor` | ❌ No | ❌ No | ❌ No |
| **Multi-guard support** | ✅ Via panels | ✅ Yes | ✅ Yes | ✅ Yes |
| **Teams / tenants** | ✅ Feature flag | ✅ Yes | ✅ Built-in | ✅ Yes |
| **Filament integration** | ✅ `azguard/filament` | ✅ Community pkg | ⚠️ Unofficial | ❌ None |
| **GitHub Stars** | 🔒 Private | ~7k | ~4k | ~2k |
| **Laravel support** | 11, 12 | 10, 11, 12 | 10, 11, 12 | 10, 11 |

---

## Spatie Permission

[spatie/laravel-permission](https://github.com/spatie/laravel-permission) is the most popular Laravel RBAC package with ~7,000 stars.

### Strengths
- Massive community, extensive documentation
- Both role-based and direct permissions on users
- Good Blade directive support
- Flexible permission guard system
- Mature, battle-tested in thousands of projects

### Weaknesses
- **Permissions are magic strings** — `$user->givePermissionTo('edit-posts')` has no type safety
- **All definitions in DB** — can't diff access changes in a PR, drift between environments
- **Cache issues at scale** — with 100+ permissions, the cache key can exceed 4MB; known issues with Octane
- **No panel scoping** — all permissions are global, easy to accidentally grant cross-zone access
- **No doctor/lint tooling** — typos in permission strings fail silently at runtime
- **No PHP Attribute support** — manual `$this->authorize()` calls everywhere

### Best for
Projects where admins need to create/edit permissions at runtime from a UI, or teams deeply familiar with Spatie's ecosystem.

---

## Bouncer

[silber/bouncer](https://github.com/JosephSilber/bouncer) is a lightweight, elegant alternative from Joseph Silber.

### Strengths
- Clean, minimal API
- Excellent multi-tenancy via "scopes"
- Allows assigning abilities to specific model instances (e.g. can edit *this specific post*)
- Good test coverage and stability
- "Forbid" API — explicitly deny abilities even if a role grants them

### Weaknesses
- **No panel concept** — no namespaced permission zones
- **Database-first** — all abilities defined at runtime, not in code
- **Less active development** — last major update was 2022
- **No PHP Attribute support**
- **No Filament integration** (community only)
- **No artisan tooling** beyond basic commands

### Best for
Apps needing model-level permissions (can edit *this specific resource*) or complex forbid logic.

---

## Laratrust

[santigarcor/laratrust](https://github.com/santigarcor/laratrust) is a traditional RBAC/RBAC-with-teams package.

### Strengths
- Team-based permissions built-in
- Supports multiple user models natively
- Mature, predictable API

### Weaknesses
- **No multi-panel scoping** — teams are global, not zone-based
- **Database-first** like Spatie and Bouncer
- **No PHP Attributes**
- **Limited Octane support**
- **No doctor/lint tooling**
- Less active community vs Spatie

### Best for
Classic multi-tenant SaaS where each tenant is a "team" and permissions are the same across all tenants.

---

## Summary

The fundamental tradeoff is **flexibility at runtime** (Spatie/Bouncer/Laratrust) vs **safety and traceability in code** (AzGuard).

If your product manager needs to add new permission types from an admin panel without a developer — use Spatie.

If your team needs to code-review every access control change, catch typos at the IDE level, and run PHPStan on authorization logic — use AzGuard.
