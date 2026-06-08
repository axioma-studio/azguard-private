# Blade Directives

AzGuard integrates with Laravel's native Gate layer. All standard Laravel Blade directives work out of the box — no extra package or service provider setup required.

::: tip Prefer permission directives over role directives
Always check a **permission** in Blade, not a **role**. `@can('app.documents.edit')` will remain correct even if you later reorganize your roles. Checking `@role('editor')` is fragile because the role name or its permissions may change.
:::

## `@can` / `@cannot`

The primary directive. Checks a Gate ability registered with `#[GateAbility]`:

```blade
{{-- Show if the user can edit documents --}}
@can('app.documents.edit')
    <a href="{{ route('documents.edit', $document) }}" class="btn">Edit</a>
@endcan

{{-- Show a fallback --}}
@cannot('app.documents.delete')
    <p class="text-muted">You don't have permission to delete documents.</p>
@endcannot

{{-- With else branch --}}
@can('app.documents.create')
    <a href="{{ route('documents.create') }}">New document</a>
@else
    <span class="text-gray-400">Read-only access</span>
@endcan
```

## `@canany`

Passes if the user has **at least one** of the listed abilities:

```blade
@canany(['app.documents.edit', 'app.documents.delete'])
    <div class="actions">
        @can('app.documents.edit')
            <a href="{{ route('documents.edit', $document) }}">Edit</a>
        @endcan

        @can('app.documents.delete')
            <button wire:click="delete">Delete</button>
        @endcan
    </div>
@endcanany
```

## With model argument (policy-style)

Pass a model as the second argument to route through Laravel's policy system:

```blade
@can('app.documents.edit', $document)
    <button type="button">Edit</button>
@endcan
```

If a policy exists for `Document`, Laravel will call `DocumentPolicy::edit($user, $document)`. Otherwise it falls back to the Gate ability check.

## Checking roles in Blade

Blade does not have a built-in `@role` directive. The idiomatic approach is to use `@if` with the trait method:

```blade
@if(auth()->user()?->hasRole('admin'))
    <a href="/admin">Admin panel</a>
@endif
```

If you prefer a cleaner syntax, register custom Blade directives in your `AppServiceProvider`:

```php
use Illuminate\Support\Facades\Blade;

public function boot(): void
{
    Blade::directive('role', function (string $expression) {
        return "<?php if(auth()->check() && auth()->user()->hasRole({$expression})): ?>";
    });

    Blade::directive('endrole', function () {
        return '<?php endif; ?>';
    });

    Blade::directive('hasanyrole', function (string $expression) {
        return "<?php if(auth()->check() && auth()->user()->hasAnyRole({$expression})): ?>";
    });

    Blade::directive('endhasanyrole', function () {
        return '<?php endif; ?>';
    });

    Blade::directive('hasallroles', function (string $expression) {
        return "<?php if(auth()->check() && auth()->user()->hasAllRoles({$expression})): ?>";
    });

    Blade::directive('endhasallroles', function () {
        return '<?php endif; ?>';
    });

    Blade::directive('unlessrole', function (string $expression) {
        return "<?php if(!auth()->check() || !auth()->user()->hasRole({$expression})): ?>";
    });

    Blade::directive('endunlessrole', function () {
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
    <a href="/cms">CMS</a>
@endhasanyrole

@unlessrole('viewer')
    <button>Advanced actions</button>
@endunlessrole
```

::: warning
Custom role directives bypass the Gate and do not go through policies. Use them **only for UI hints** (showing/hiding elements). For actual access control on routes or controllers, always use `can:` middleware or `$this->authorize()`, which go through the Gate.
:::

## In Livewire components

In Livewire, access control belongs in the component class, not the template:

```php
// In your Livewire component
public function canEdit(): bool
{
    return $this->authorize('app.documents.edit');
}

// Or check without throwing:
public function canDelete(): bool
{
    return auth()->user()?->hasPermission(DocumentsPermission::Delete) ?? false;
}
```

```blade
@if($this->canEdit())
    <button wire:click="save">Save</button>
@endif

@if($this->canDelete())
    <button wire:click="delete" class="danger">Delete</button>
@endif
```

## Performance note

Gate checks with AzGuard are resolved per-request from an in-memory permission set. Calling `@can('app.documents.view')` multiple times on the same page does not re-query the database.
