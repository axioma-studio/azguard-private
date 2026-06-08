---
layout: home

hero:
  name: AzGuard
  text: Code-first RBAC for Laravel
  tagline: Roles are PHP classes. Permissions live in Git. No magic, no migrations per feature.
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
    details: Roles are plain PHP classes with typed permission arrays. Diffable, testable, version-controlled.

  - icon: 🎛️
    title: Multi-Panel Isolation
    details: app.* and admin.* permission namespaces are fully isolated. One user model, multiple independent access contexts.

  - icon: ⚡
    title: Laravel Gate Native
    details: Plugs into Gate::before(). Works with @can, Gate::allows(), and policies — no new API to learn.

  - icon: 🩺
    title: Built-in Doctor
    details: artisan azguard:doctor scans your config, migrations, and role definitions and reports mismatches before they hit prod.

  - icon: 🎯
    title: Direct Grants
    details: Grant individual permissions to a user without assigning a role. Perfect for temporary or exception-based access.

  - icon: 🔌
    title: Context (opt-in)
    details: Attach a runtime context (tenant, team, project) to every permission check. Zero overhead when not used.
---

## Three lines that tell the story

::: code-group

```php [1. Define]
enum DocumentsPermission: string implements PermissionInterface
{
    #[GateAbility]  // auto-registered with Gate
    case View   = 'documents.view';
    case Create = 'documents.create';
    case Edit   = 'documents.edit';
    case Delete = 'documents.delete';
}
```

```php [2. Protect]
// Controller method — declarative, IDE-friendly
#[CheckPermission(permission: DocumentsPermission::View, arguments: ['document'])]
public function show(Document $document): Response
{
    return Inertia::render('Documents/Show', [
        'document'  => $document,
        'abilities' => DocumentsAbilities::fromDocument($document)->toArray(),
    ]);
}
```

```php [3. Check]
// Anywhere in your codebase
$user->hasAzPermission(DocumentsPermission::View);  // bool

Gate::allows('app.documents.view', $document);       // Laravel Gate

// Blade
@azcan('documents.view')
    <a href="...">View document</a>
@endazcan
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
| **Filament v3+** | ✅ First-party package | ⚠️ Community plugin |

→ [Full comparison including Bouncer & Laratrust](/guide/comparison)
