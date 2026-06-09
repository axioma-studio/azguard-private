# Blade-директивы

AzGuard работает со всеми стандартными Blade-директивами Laravel и добавляет собственные для работы с ролями.

## Стандартные директивы Laravel Gate

```blade
@can('app.posts.edit')
    <a href="{{ route('posts.edit', $post) }}">Редактировать</a>
@endcan

@cannot('app.posts.delete')
    <span>Удаление недоступно</span>
@endcannot

@canany(['app.posts.edit', 'app.posts.delete'])
    <div класс="действия">…</div>
@endcanany
```

## Директивы AzGuard для ролей

```blade
@role(EditorRole::class)
    <nav>Меню редактора</nav>
@endrole

@hasrole(AdminRole::class)
    <a href="/admin">Панель администратора</a>
@endhasrole

@unlessrole(AdminRole::class)
    <p>Обычный пользовательский интерфейс</p>
@endunlessrole
```

## Использование с enum-классами

```blade
{{-- Передавайте полный ключ Gate или enum-кейс в @can --}}
@can('app.documents.view')
    {{ $doc->title }}
@endcan

{{-- Для компонентов Livewire --}}
@php($canEdit = auth()->user()?->hasPermission(DocumentsPermission::Edit))
<livewire:document-editor :editable="$canEdit" />
```

## Проверка с моделью (через Policy)

```blade
@can('update', $post)
    <button тип="submit">Сохранить</button>
@endcan
```

::: tip
`@can` + модель маршрутизируется через Laravel Policy, которая может использовать `hasPermission()` внутри.
:::

→ [Политики и Gate](/ru/guide/policies-and-gates) · [Права на фронтенде](/ru/guide/abilities-frontend)
