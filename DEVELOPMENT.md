# Руководство по разработке AzGuard

## 🛠 Локальная разработка

### Подключение к тестовому проекту

Симлинк (пример CRM):

```bash
ln -sfn ../../../packages/azguard /path/to/laravel/packages/azguard
```

`composer.json` Laravel-проекта:

```json
"repositories": [
    {
        "type": "path",
        "url": "packages/azguard/packages/core",
        "options": { "symlink": true }
    }
],
"require": {
    "azguard/azguard": "@dev"
}
```

### Docker (пример axioma-studio/crm)

- Volume: `../../packages/azguard:/var/www/html/packages/azguard:ro`
- Запуск: `docker compose --env-file .env --env-file .env.local up -d`
- Postgres на хосте: `PGSQL_PORT=15433` в `.env.local` (не 5432)

### Контракт роли

| Поле | Значение |
|------|----------|
| `roles.name` | slug: `admin`, `member` |
| `roles.class_name` | FQCN класса роли: `App\Guards\App\Roles\AdminRole` |

### Middleware

- Alias: `azguard.roles` — eager load `roles` для auth user
- В Laravel 11+: `$middleware->alias(['azguard.roles' => \AzGuard\Http\Middleware\LoadAzGuardRoles::class])` или alias из провайдера пакета

## 🌿 Git Workflow
1. Создайте ветку `feature/` или `fix/` от `develop`.
2. Напишите код и тесты.
3. Запустите `composer lint` для очистки стиля.
4. Создайте Pull Request в `develop`.

## 🧪 Команды качества
| Команда | Инструмент | Описание |
|---------|------------|----------|
| `composer lint` | Pint | Исправляет стиль кода по стандартам Laravel |
| `composer refactor` | Rector | Автоматический рефакторинг и апгрейд PHP |
| `composer test` | Pest | Запуск всех тестов проекта |

Для интеграционных тестов (Feature) нужен PHP-драйвер SQLite: установите пакет `php-sqlite3` (или включите расширение в `php.ini`).
