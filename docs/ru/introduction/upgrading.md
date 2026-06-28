# Обновление

## С версии 0.x до 1.0

::: warning
Версия 1.0 содержит breaking changes в структуре конфигурации и именовании.
:::

### 1. Обновите Composer-зависимость

```bash
composer require axioma-studio/azguard-core:^1.0
```

### 2. Обновите конфигурацию

Опубликуйте новую версию конфига:

```bash
php artisan vendor:publish --tag=az-guard-config --force
```

Сравните `config/az-guard.php` с предыдущей версией. Ключевые изменения:

| Старый ключ | Новый ключ |
|---|---|
| `cache.driver` | `cache.store` |
| `cache.ttl` | `cache.expiration_time` |
| `role_namespace` | *(удалён, теперь из конфига панели)* |

### 3. Обновите миграции

Миграции AzGuard загружаются автоматически (`loadMigrationsFrom`), публиковать их не нужно:

```bash
php artisan migrate
```

### 4. Обновите базовый класс ролей

Если вы использовали `AzGuard\Role` как базовый класс — замените на
`AzGuard\Roles\BaseRole`:

```php
// До
class EditorRole extends \AzGuard\Role { ... }

// После
class EditorRole extends \AzGuard\Roles\BaseRole { ... }
```

### 5. Сбросьте кэш

```bash
php artisan cache:clear
php artisan guard:cache-reset
```

## Совместимость между патч-версиями

Внутри одной мажорной версии (1.x) AzGuard следует SemVer. Обновления патч-версий (1.0.x → 1.0.y) не требуют изменений кода.

→ [Список изменений](/ru/introduction/changelog)
