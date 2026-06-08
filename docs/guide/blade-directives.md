# Blade Directives

AzGuard integrates with Laravel's native `@can` / `@cannot` directives through the Gate. No custom directives are required.

## Basic usage

```blade
{{-- Show a button only if the user can edit documents --}}
@can('app.documents.edit')
    <a href="{{ route('documents.edit', $document) }}" class="btn">
        Edit
    </a>
@endcan

{{-- Show a fallback for users without access --}}
@cannot('app.documents.delete')
    <p class="text-muted">You don't have permission to delete documents.</p>
@endcannot

{{-- else branch --}}
@can('app.documents.create')
    <a href="{{ route('documents.create') }}">New document</a>
@else
    <span>Read-only access</span>
@endcan
```

## With model arguments

```blade
{{-- Policy-style: pass the model as second argument --}}
@can('app.documents.edit', $document)
    <button type="button">Edit</button>
@endcan
```

This routes through Laravel's policy system if a policy exists for `Document`.

## Checking roles

Blade does not have a built-in `@hasrole` directive. Use `@if` with the trait method:

```blade
@if(auth()->user()?->hasRole('admin'))
    <a href="/admin">Admin panel</a>
@endif
```

Or create your own Blade directive in `AppServiceProvider`:

```php
use Illuminate\Support\Facades\Blade;

public function boot(): void
{
    Blade::directive('role', function (string $role) {
        return "<?php if(auth()->user()?->hasRole({$role})): ?>";
    });

    Blade::directive('endrole', function () {
        return '<?php endif; ?>';
    });
}
```

```blade
@role('admin')
    <a href="/admin">Admin panel</a>
@endrole
```

## In Livewire components

```php
// In your Livewire component
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

## Performance note

Gate checks with AzGuard are cached per-request. Calling `@can('app.documents.view')` multiple times on the same page does not re-query the database.
