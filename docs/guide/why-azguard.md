# Why AzGuard?

## The Problem We Solved

When building Laravel applications with complex access rules — multi-panel admin dashboards, SaaS tenants, multi-role systems — we kept hitting the same wall with existing packages:

**Permissions defined in the database are invisible to the codebase.**

You'd have:
- Magic strings like `'edit-posts'` scattered across controllers, seeders, and documentation
- No IDE autocompletion, no compile-time validation
- Typos that only fail at runtime in production
- `php artisan db:seed` races in CI pipelines
- Permission lists that diverged between dev, staging, and production environments
- Cache invalidation nightmares when deploying to Kubernetes or using Laravel Octane

We tried Spatie Permission, Bouncer, and Laratrust. Each has great adoption, but all share the same architectural tradeoff: **the source of truth is the database, not the code**.

## The Core Idea

AzGuard inverts this: **the source of truth is PHP**.

```php
// This is your permission — a PHP Enum case.
// IDE can autocomplete it. PHPStan can validate it. Git can track it.
enum AppPermissions: string
{
    case PostsEdit   = 'posts.edit';
    case PostsDelete = 'posts.delete';
    case UsersView   = 'users.view';
}

// This is your role — a PHP class.
// It says exactly what it grants. No database lookup needed to understand it.
final class EditorRole extends BaseRole
{
    public function permissions(): array
    {
        return [
            AppPermissions::PostsEdit->value,
        ];
    }
}
```

The database still exists — it stores **which user has which role**. But the permission definitions themselves live in code.

## What Makes AzGuard Different

### 1. Panels — Scoped Permission Namespaces

Most applications have multiple "zones": a public-facing app, an admin dashboard, an API. AzGuard formalizes this:

```
admin.users.delete   ← admin panel
app.posts.edit       ← app panel
api.webhooks.create  ← api panel
```

Each panel is a PHP class (`PanelProvider`). Roles within one panel cannot accidentally grant access in another.

### 2. PHP 8 Attributes for Declarative Access Control

Instead of manually calling `$this->authorize()` in every controller method:

```php
// Before AzGuard
public function update(Request $request, Post $post): Response
{
    $this->authorize('edit-posts');
    // ...
}

// With AzGuard
#[CheckPermission(AppPermissions::PostsEdit, arguments: ['post'])]
public function update(Request $request, Post $post): Response
{
    // Access already verified declaratively
}
```

### 3. Native Laravel Gate Integration

AzGuard hooks into `Gate::before()` and works with every standard Laravel auth feature — no parallel system to maintain:

```php
Gate::allows('app.posts.edit');        // ✅ works
$this->authorize('update', $post);     // ✅ works via policy
@can('app.posts.edit') ... @endcan    // ✅ works
```

### 4. Developer Experience

```bash
php artisan azguard:doctor            # Finds orphaned policies, missing abilities, typos
php artisan azguard:list-permissions  # Shows all permissions across all panels
php artisan azguard:sync-roles        # Syncs PHP role classes to DB
php artisan azguard:sync-roles --dry-run  # Preview without writing
```

## When to Use AzGuard

AzGuard is the right choice when:

- Your application has **multiple access zones** (admin, app, API)
- You want **permissions reviewable in pull requests** like any other code
- You have a **team** and want IDE support + static analysis on access control
- You use **Laravel Octane** or deploy to **Kubernetes** (cross-request cache works with Redis, not app memory)
- You need **multi-panel RBAC** with permission isolation between zones

AzGuard may not be the right choice when:

- You need **runtime-editable permissions** by non-developers (e.g. admin UI where clients configure their own roles from scratch)
- You have a very simple app with 2-3 fixed roles that never change
- You are migrating a legacy app already deeply integrated with Spatie's DB schema
