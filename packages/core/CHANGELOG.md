# Changelog

## 0.2.0

- `Gate::before` через `Authorizer`
- Контракт роли: `name` = slug, `class_name` = FQCN класса роли
- `hasAzPermission` поддерживает `*`
- `PermissionName`, middleware `azguard.roles`
- `PanelProvider` регистрирует abilities из `Permissions/*Permission::map()`
- Laravel 13 в `illuminate/*`
