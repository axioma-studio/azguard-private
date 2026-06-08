# Требования

Перед установкой AzGuard убедитесь, что ваше окружение соответствует следующим требованиям.

## Системные требования

| Зависимость | Минимальная версия |
|---|---|
| PHP | 8.2 |
| Laravel | 11.x или 12.x |
| База данных | MySQL 8 / PostgreSQL 14 / SQLite 3.35+ |

## PHP-расширения

AzGuard использует стандартную инфраструктуру Laravel. Никаких нестандартных расширений не нужно — достаточно того, что есть в стандартной установке Laravel:

- `ext-pdo` — доступ к БД
- `ext-json` — сериализация
- `ext-mbstring` — работа со строками

## Модель User: контракт `Authorizable`

AzGuard подключается к Gate-слою Laravel. Чтобы `can()`, `authorize()` и политики работали, ваша модель `User` **должна реализовывать** контракт `Illuminate\Contracts\Auth\Access\Authorizable`.

Проще всего — наследоваться от `Authenticatable` и добавить трейт:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable
{
    use HasAzGuard;
}
```

Если вы используете собственный базовый класс, убедитесь, что он реализует `Authorizable` — иначе `$user->can()` и `Gate::allows()` будут молча возвращать `false`.

## Зарезервированные имена

::: danger Конфликты имён вызывают скрытые ошибки
**Не определяйте** следующее в вашей модели User. Эти имена зарезервированы AzGuard — конфликты приводят к непредсказуемому поведению:

- `role` / `roles` — свойство, колонка БД, Eloquent-связь или метод `roles()`
- `permission` / `permissions` — свойство, колонка БД, Eloquent-связь или метод `permissions()`
:::

Если в модели уже есть одно из этих имён, переименуйте его до установки. Например, легаси-проперти `$user->roles` переименуйте в `$user->role_list`.

## Конфиг-файл

AzGuard публикует `config/az-guard.php`. Если такой файл уже есть, удалите его перед публикацией:

```bash
ls config/az-guard.php
php artisan vendor:publish --tag=az-guard-config
```

## Длина ключа индекса (MySQL)

::: warning MySQL utf8mb4
MySQL 8+ с `utf8mb4` ограничивает длину составных ключей. Выберите один из способов до запуска миграций:

**Вариант 1 (рекомендуется):** InnoDB с `ROW_FORMAT=Dynamic` (по умолчанию в MySQL 8.0+). Изменений кода не требуется.

**Вариант 2:** Укажите максимальную длину строки в `AppServiceProvider::boot()`:
```php
use Illuminate\Support\Facades\Schema;

public function boot(): void
{
    Schema::defaultStringLength(125);
}
```

**Вариант 3:** Публикуйте миграции и вручную укажите короткие длины полей:
```php
$table->string('name', 125);
$table->string('guard_name', 25);
```
:::

## UUID / ULID первичные ключи

По умолчанию AzGuard предполагает **auto-increment integer** PK. Если вы используете UUID/ULID:

1. `php artisan vendor:publish --tag=az-guard-migrations`
2. Измените пивот-таблицы: `uuid` / `ulid` вместо `unsignedBigInteger`
3. В `config/az-guard.php` установите `model_morph_key_type` → `'uuid'` или `'ulid'`

Подробнее: [UUID / ULID](/ru/guide/uuid-ulid).

## Внешние ключи

Миграции AzGuard создают FK-ограничения `onDelete('cascade')`. Если движок БД не поддерживает FK (MyISAM, некоторые конфигурации SQLite) — публикуйте миграции и удалите `->foreign()` вручную.

## Несколько guards

Если в приложении несколько guards (`web`, `api`), каждый получает собственный набор ролей и прав. См.: [Несколько Guards](/ru/guide/multiple-guards).

## Совместимость с Octane

AzGuard безсостоятелен по дизайну — нет статических кэшей между запросами. Работает с [Laravel Octane](https://laravel.com/docs/octane) (Swoole и RoadRunner) без дополнительной настройки.
