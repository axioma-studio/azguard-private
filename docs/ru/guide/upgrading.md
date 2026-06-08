# Обновление

## С 0.x до 1.0

::: warning Критические изменения
Версия 1.0 содержит несовместимые изменения в API.
:::

### Изменения пространств имён

```php
// Было
use AzGuard\Traits\HasPermissions;

// Стало
use AzGuard\Concerns\HasAzGuard;
```

### Изменения миграций

Запустите новые миграции после обновления:

```bash
php artisan vendor:publish --tag=azguard-migrations --force
php artisan migrate
```

### Изменения конфигурации

Переопубликуйте конфигурацию:

```bash
php artisan vendor:publish --tag=azguard-config --force
```

Сверьте `config/azguard.php` с новой структурой — ключ `panels` теперь обязателен.
