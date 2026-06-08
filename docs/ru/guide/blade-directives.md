# Blade-директивы

AzGuard регистрирует стандартные директивы Laravel — никакого нового API учить не нужно.

## Основные директивы

```blade
{{-- Проверка права --}}
@can('app.posts.edit')
    <a href="{{ route('posts.edit', $post) }}">Редактировать</a>
@endcan

{{-- Через enum-кейс (Laravel 10+) --}}
@can(\App\AzGuard\App\Permissions\PostsPermission::Edit->fullKey())
    <button>Редактировать</button>
@endcan

{{-- Отрицание --}}
@cannot('app.posts.delete')
    <span>Недостаточно прав для удаления</span>
@endcannot

{{-- Проверка роли --}}
@role(\App\AzGuard\App\Roles\AdminRole::class)
    <nav class="admin-nav">...</nav>
@endrole

{{-- Проверка нескольких ролей --}}
@hasanyrole([AdminRole::class, ModeratorRole::class])
    <div class="mod-tools">...</div>
@endhasanyrole
```

## @canany / @else

```blade
@canany(['app.posts.edit', 'app.posts.delete'])
    <div class="post-actions">
        @can('app.posts.edit')
            <a href="...">Изменить</a>
        @endcan
        @can('app.posts.delete')
            <button>Удалить</button>
        @endcan
    </div>
@endcanany
```

## С моделью (Policy)

```blade
@can('update', $post)
    <a href="{{ route('posts.edit', $post) }}">Редактировать</a>
@endcan

@can('delete', $post)
    <form method="POST" action="{{ route('posts.destroy', $post) }}">
        @csrf @method('DELETE')
        <button>Удалить</button>
    </form>
@endcan
```

::: warning
Директива `@role` — собственная директива AzGuard. `@can` / `@cannot` — стандартные Laravel, проксирующие через Gate.
:::
