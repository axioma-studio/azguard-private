# Blade Directives

AzGuard integrates with Laravel's native Gate, so all standard `@can` / `@cannot` / `@canany` Blade directives work out of the box — no additional setup required.

::: tip Use permission directives, not role directives
Always prefer `@can('app.documents.edit')` over `@hasrole('editor')`. Permissions express *what* can be done; roles express *who* someone is. Permissions stay stable as your application grows, roles change.
:::

## `@can` / `@cannot`

```blade
{{-- Show only if the user has this permission --}}
@can('app.documents.edit')
    <a href="{{ route('documents.edit', $document) }}" class="btn">
        Edit
    </a>
@endcan

{{-- Show fallback when access is missing --}}
@cannot('app.documents.delete')
    <p class="text-muted">You don't have permission to delete documents.</p>
@endcannot

{{-- With an else branch --}}
@can('app.documents.create')
    <a href="{{ route('documents.create') }}">New document</a>
@else
    <span>Read-only access</span>
@endcan
```

## `@canany`

Renders the block if the user has **at least one** of the listed permissions:

```blade
@canany(['app.documents.edit', 'app.documents.delete'])
    <div class="actions">
        @can('app.documents.edit')
            <a href="{{ route('documents.edit', $document) }}">Edit</a>
        @endcan

        @can('app.documents.delete')
            <button type="submit">Delete</button>
        @endcan
    </div>
@endcanany
```

## With model arguments (policy-style)

Passing a model as the second argument routes the check through Laravel's policy system:

```blade
@can('app.documents.edit', $document)
    <button type="button">Edit this document</button>
@endcan
```

This works if you have a `DocumentPolicy` registered in `AuthServiceProvider`. Without a policy, Gate falls back to permission string matching.

## Checking roles in Blade

Blade has no built-in `@hasrole` directive. Use `@if` with the trait method:

```blade
@if(auth()->user()?->hasRole('admin'))
    <a href="/admin">Admin panel</a>
@endif

@if(auth()->user()?->hasAnyRole(['editor', 'admin']))
    <a href="/dashboard">Dashboard</a>
@endif
```

::: warning Don't gate features with role checks
If you find yourself writing `@if(auth()->user()->hasRole('admin'))` everywhere, that's a sign those should be explicit permissions. Create `AdminPermission::AccessPanel` and check `@can('admin.panel.access')` instead.
:::

## Custom `@role` / `@endrole` directive

If you still want a `@role` shorthand, register it in `AppServiceProvider`:

```php
use Illuminate\Support\Facades\Blade;

public function boot(): void
{
    Blade::directive('role', function (string $expression) {
        return "<?php if(auth()->user()?->hasRole({$expression})): ?>";
    });

    Blade::directive('endrole', function () {
        return '<?php endif; ?>';
    });

    Blade::directive('hasanyrole', function (string $expression) {
        return "<?php if(auth()->user()?->hasAnyRole({$expression})): ?>";
    });

    Blade::directive('endhasanyrole', function () {
        return '<?php endif; ?>';
    });
}
```

Usage:

```blade
@role('admin')
    <a href="/admin">Admin panel</a>
@endrole

@hasanyrole(['editor', 'manager'])
    <a href="/editor">Content Studio</a>
@endhasanyrole
```

## In Livewire components

Define a computed method and use it in the template:

```php
// In your Livewire component
public function canEdit(): bool
{
    return $this->user->hasPermission(DocumentsPermission::Edit);
}

public function canDelete(): bool
{
    return Gate::allows('app.documents.delete');
}
```

```blade
@if($this->canEdit())
    <button wire:click="openEditModal">Edit</button>
@endif

@if($this->canDelete())
    <button wire:click="delete" class="btn-danger">Delete</button>
@endif
```

Alternatively, use `@can` directly — it works inside Livewire views the same way:

```blade
@can('app.documents.edit')
    <button wire:click="openEditModal">Edit</button>
@endcan
```

## Direct grants in Blade

For permissions granted directly to a user (not via roles), use the `@azdirect` directive:

```blade
@azdirect('app.documents.export')
    <button>Export</button>
@endazdirect
```

See [Direct Grants](./direct-grants.md) for the full reference.

## Performance note

Gate checks in AzGuard are resolved once per request and cached in memory. Calling `@can('app.documents.view')` five times on the same page costs one resolution, not five queries. No additional caching is needed for Blade.
