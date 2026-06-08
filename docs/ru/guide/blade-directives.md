# Blade-директивы

AzGuard регистрирует собственные Blade-директивы поверх стандартных `@can` / `@cannot` Laravel.

## Стандартные директивы Gate (работают без изменений)

```blade
{{-- Одно разрешение --}}
@can('app.posts.edit')
    <a href="{{ route('posts.edit', $post) }}">Редактировать</a>
@endcan

@cannot('app.posts.delete')
    <span class="text-muted">Удаление недоступно</span>
@endcannot

{{-- С моделью (через Policy) --}}
@can('update', $post)
    <button>Сохранить</button>
@endcan
```

## Директивы AzGuard

### @role / @endrole

```blade
@role('editor')
    <span class="badge badge-editor">Редактор</span>
@endrole

@role('admin')
    <a href="/admin">Панель администратора</a>
@else
    <span>Нет доступа</span>
@endrole
```

### @hasanypermission / @endhasanypermission

```blade
@hasanypermission(['app.posts.edit', 'app.posts.delete'])
    <div class="actions">
        @can('app.posts.edit')
            <a href="...">Ред.</a>
        @endcan
        @can('app.posts.delete')
            <button>Удалить</button>
        @endcan
    </div>
@endhasanypermission
```

### @hasallpermissions / @endhasallpermissions

```blade
@hasallpermissions(['app.reports.view', 'app.reports.export'])
    <a href="/reports/export">Экспорт отчёта</a>
@endhasallpermissions
```

### @unlessrole / @endunlessrole

```blade
@unlessrole('admin')
    <p>Расширенные настройки доступны только администраторам.</p>
@endunlessrole
```

## Производительность

Все директивы используют кешированные данные из `hasPermission()`. Многократный вызов в одном шаблоне не порождает лишних запросов к БД.
