# Blade Directives

AzGuard integrates with Laravel's native Gate layer, which means **all standard Laravel Blade directives work out of the box** — no additional setup required.

::: tip Permission-first
Always prefer **permission directives** (`@can`, `@cannot`, `@canany`, `@haspermission`) over role directives (`@role`, `@hasrole`). Permissions are stable; roles change. Code that checks permissions stays valid even when you rename or restructure roles.
:::

## Permission directives

### `@can` / `@cannot`

```blade
{{-- Check a single permission --}}
@can('app.documents.edit')
    <a href="{{ route('documents.edit', $document) }}" class="btn">Edit</a>
@endcan

@cannot('app.documents.delete')
    <p class="text-muted">You don't have permission to delete documents.</p>
@endcannot

{{-- With an else branch --}}
@can('app.documents.create')
    <a href="{{ route('documents.create') }}">New document</a>
@else
    <span class="text-muted">Read-only access</span>
@endcan
```

### `@canany`

Passes if the user has **at least one** of the listed permissions:

```blade
@canany(['app.documents.create', 'app.documents.edit'])
    <div class="editor-toolbar">
        {{-- shown to users who can either create or edit --}}
    </div>
@endcanany
```

There is no `@canall` directive. To check for all permissions, chain multiple `@can` blocks or use `$user->hasAllPermissions()` in a condition.

### `@haspermission`

An alias for `@can` specific to AzGuard permission strings:

```blade
@haspermission('app.documents.view')
    <a href="/documents">Documents</a>
@endhaspermission

{{-- With explicit guard --}}
@haspermission('app.documents.view', 'web')
    <a href="/documents">Documents</a>
@endhaspermission
```

### With model arguments (Policy-style)

```blade
{{-- Routes through Laravel's Policy system if a policy exists for Document --}}
@can('app.documents.edit', $document)
    <button type="button">Edit</button>
@endcan
```

### Checking with a specific guard

```blade
{{-- Useful in multi-guard apps --}}
@can('admin.users.manage', 'admin')
    <a href="/admin/users">Manage users</a>
@endcan
```

## Role directives

::: warning Use permissions instead of roles in templates
Role checks in templates create tight coupling between your UI and your role structure. When you rename or split a role, you must update every template. Permission checks are stable — use them wherever possible.

The only legitimate use of role directives is displaying role-specific UI chrome (e.g. "Admin Panel" link in the nav) where the *concept of a role* is what matters, not a specific capability.
:::

### `@role` / `@hasrole`

Check for a single role. Both directives are identical:

```blade
@role('admin')
    <a href="/admin">Admin panel</a>
@else
    <span>Not an admin</span>
@endrole

{{-- identical --}}
@hasrole('admin')
    <a href="/admin">Admin panel</a>
@else
    <span>Not an admin</span>
@endhasrole
```

With an explicit guard:

```blade
@role('admin', 'web')
    ...
@endrole
```

### `@hasanyrole`

Passes if the user has **at least one** of the listed roles. You can pass a pipe-separated string or a collection:

```blade
@hasanyrole('editor|admin')
    <div class="editor-toolbar">...</div>
@else
    <div class="read-only">...</div>
@endhasanyrole

{{-- With a collection --}}
@hasanyrole($roles)
    I have one or more of these roles!
@endhasanyrole
```

### `@hasallroles`

Passes only if the user has **every** listed role:

```blade
@hasallroles('editor|moderator')
    <span>Full access granted</span>
@else
    <span>Missing at least one required role</span>
@endhasallroles

{{-- With a collection --}}
@hasallroles($collectionOfRoles)
    All matched.
@endhasallroles
```

### `@unlessrole`

The inverse of `@role` — passes when the user does **not** have the role:

```blade
@unlessrole('admin')
    <p>You are viewing the standard interface.</p>
@else
    <p>You are viewing the admin interface.</p>
@endunlessrole
```

### `@hasexactroles`

Passes only if the user has **exactly** the listed roles — no more, no less:

```blade
@hasexactroles('editor|moderator')
    {{-- user has editor AND moderator, and NO other roles --}}
    <span>Exactly editor + moderator</span>
@else
    <span>Different role set</span>
@endhasexactroles
```

This is useful when the exact role combination matters, e.g. to prevent an admin from seeing a limited-access UI.

## In Livewire components

Prefer computing access in the component class and exposing it as a read-only property or method:

```php
// In your Livewire component class
public function canEdit(): bool
{
    return $this->user->hasPermission(DocumentsPermission::Edit);
}
```

```blade
@if($this->canEdit())
    <button wire:click="edit">Edit</button>
@endif
```

This keeps authorization logic out of templates and makes it easy to test.

## In controller conditions

For non-Gate checks in controllers you can also use `@if` with the trait methods:

```blade
@if(auth()->user()?->hasRole('admin'))
    <a href="/admin">Admin panel</a>
@endif

@if(auth()->user()?->hasPermission(DocumentsPermission::Delete))
    <button class="btn-danger">Delete</button>
@endif
```

## Performance note

Gate checks with AzGuard are resolved per-request from in-memory role definitions and cached within the request lifecycle. Calling `@can('app.documents.view')` multiple times on the same page does **not** re-query the database.
