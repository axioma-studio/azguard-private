# Permissions

Permission — backed enum в `Guards/{Panel}/{Domain}/Permissions/`.

## Примеры ключей

| Тип | Пример `->value` | Resolved (`app`) |
|-----|------------------|------------------|
| CRUD модели | `documents.view` | `app.documents.view` |
| Действие API | `documents.versions.create` | `app.documents.versions.create` |
| Workflow | `documents.workflow.publish` | `app.documents.workflow.publish` |
| Раздел UI | `dashboard.view` | `app.dashboard.view` |

## Роли

В `permissions()` роли — только **resolved** строки:

```php
AppGuard::permission(DocumentsPermission::View);
```

## TypeScript

При необходимости — `#[TypeScript]` на enum в приложении.
