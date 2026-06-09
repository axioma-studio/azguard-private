# Обновление

## С версии 0.x до 1.0

::: warning
Версия 1.0 содержит breaking changes в структуре конфигурации и именовании миграций.
:::

### 1. Обновите Composer-зависимость

```bash
composer require axioma-studio/azguard:^1.0
```

### 2. Обновите конфигурацию

Опубликуйте новую версию конфига:

```bash
php artisan vendor:publish --tag=azguard-config --force
```

Сравните `config/azguard.php` с предыдущей версией. Ключевые изменения:

| Старый ключ | Новый ключ |
|---|---|
| `panels.default` | `panels.app` |
| `cache.driver` | `cache.store` |
| `role_namespace` | *(удалён, теперь из конфига панели)* |

### 3. Обновите миграции

```bash
php artisan vendor:publish --tag=azguard-migrations --force
php artisan migrate
```

### 4. Обновите пространства имён ролей

Если вы использовали `AzGuard\Role` как базовый класс — замените на `AzGuard\Contracts\RoleInterface`:

```php
// До
class EditorRole extends \AzGuard\Role { ... }

// После
class EditorRole implements \AzGuard\Contracts\RoleInterface { ... }
```

### 5. Сбросьте кэш

```bash
php artisan cache:clear
php artisan azguard:cache-clear
```

## Совместимость между патч-версиями

Внутри одной мажорной версии (1.x) AzGuard следует SemVer. Обновления патч-версий (1.0.x → 1.0.y) не требуют изменений кода.

→ [Список изменений](/ru/guide/changelog)
