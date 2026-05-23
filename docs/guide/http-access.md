# HTTP-доступ

## Middleware

- `azguard.panel:app` — текущая панель для resolve permission.
- `azguard.roles` — eager load ролей.
- `check.access` / `azguard.check` — `#[CheckPermission]` на методе контроллера.

## Контроллер

```php
#[CheckPermission(permission: DocumentsPermission::View, arguments: ['document'])]
public function show(Document $document): Response
```

Middleware вызывает `Gate::allows(resolvedAbility, [$document])`.

## Опционально

`$this->authorize('view', $document)` — если зарегистрирован `Gate::policy`; не primary-паттерн.
