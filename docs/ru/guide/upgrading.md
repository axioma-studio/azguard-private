# Обновление

## С v0.x на v1.0

Version 1.0 вводит несколько ломающих изменений в API трейта.

### Переименования методов `HasAzGuard`

| v0.x | v1.0 |
|---|---|
| `hasAzPermission()` | `hasPermission()` |
| `giveAzPermission()` | `HasDirectGrants::directGrant()` |
| `revokeAzPermission()` | `HasDirectGrants::revokeDirectGrant()` |
| `clearAzPermissionsCache()` | `flushPermissions()` |

### Поиск и замена

Выполните в корне проекта:

```bash
grep -r 'hasAzPermission' . --include='*.php'
grep -r 'giveAzPermission' . --include='*.php'
grep -r 'clearAzPermissionsCache' . --include='*.php'
```

### Изменения конфига

Ключи конфига не переименовывались в v1.0. Ваш `config/az-guard.php` остаётся совместим.

### Изменения миграций

Новых миграций в v1.0 нет. Миграции от v0.x остаются валидными.

## Миграция с Spatie Permission

Если вы переходите с Spatie `laravel-permission`, см. [Сравнение с другими библиотеками](/ru/guide/comparison) и раздел рецептов.
