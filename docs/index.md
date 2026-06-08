---
layout: home

hero:
  name: AzGuard
  text: Code-first RBAC for Laravel
  tagline: Roles are PHP classes. Permissions live in Git. No migrations per feature, no magic strings.
  image:
    src: /logo.png
    alt: AzGuard
  actions:
    - theme: brand
      text: Get Started →
      link: /guide/quick-start
    - theme: alt
      text: Why AzGuard?
      link: /guide/why-azguard

features:
  - icon: 🏗️
    title: Code-First Roles
    details: Roles are plain PHP classes with typed permission arrays. Diffable, reviewable in PRs, version-controlled — no admin UI required.

  - icon: 🎛️
    title: Multi-Panel Isolation
    details: app.* and admin.* permission namespaces are fully isolated. One User model, multiple independent access contexts.

  - icon: ⚡
    title: Laravel Gate Native
    details: Plugs into Gate::before(). Works with @can, Gate::allows(), policies and middleware — no new API to learn.

  - icon: 🩺
    title: Built-in Doctor
    details: artisan azguard:doctor scans your config, migrations, and role definitions and reports mismatches before they reach production.

  - icon: 🎯
    title: Direct Grants
    details: Grant individual permissions to a user without assigning a role. Supports expiry dates — perfect for temporary or exception-based access.

  - icon: 🔌
    title: Context (opt-in)
    details: Attach a runtime context (tenant, team, project) to every permission check. Zero overhead when not used.
---

## Three lines that tell the story

::: code-group

```php [1. Define]
// app/AzGuard/App/Permissions/DocumentsPermission.php
enum DocumentsPermission: string implements PermissionInterface
{
    #[GateAbility]  // registers 'app.documents.view' with Laravel Gate
    case View   = 'documents.view';
    case Create = 'documents.create';
    case Edit   = 'documents.edit';
    case Delete = 'documents.delete';
}
```

```php [2. Protect]
// Controller — declarative, IDE-friendly
#[CheckPermission(DocumentsPermission::View)]
public function show(Document $document): Response
{
    return Inertia::render('Documents/Show', [
        'document' => $document,
    ]);
}
```

```php [3. Check]
// Anywhere in your codebase
$user->hasPermission(DocumentsPermission::View);  // true/false

Gate::allows('app.documents.view');               // Laravel Gate

// Blade
@can('app.documents.view')
    <a href="...">View document</a>
@endcan
```

:::

## AzGuard vs Spatie Permission

| | AzGuard | Spatie Permission |
|---|---|---|
| **Role storage** | PHP class (Git) | Database record |
| **Permission storage** | Enum case (Git) | Database record |
| **Multi-panel** | ✅ Native namespacing | ⚠️ Manual workaround |
| **PHP 8 Attributes** | ✅ `#[CheckPermission]` | ❌ No |
| **Octane safe** | ✅ Stateless | ⚠️ Known issues |
| **Type safety** | ✅ Full | ❌ Strings only |
| **Filament v3+** | ✅ First-party adapter | ⚠️ Community plugin |

→ [Full comparison including Bouncer & Laratrust](/guide/comparison)
