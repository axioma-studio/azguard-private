# Роли vs Разрешения

## Когда использовать роли, а когда прямые гранты

| Сценарий | Подход |
|---|---|
| Постоянный уровень доступа | Роль |
| Временный или исключительный доступ | Прямой грант с TTL |
| Один пользователь, одно право | Прямой грант |
| Группа пользователей, набор прав | Роль |

## Принцип минимальных привилегий

Начинайте с минимальным набором прав и добавляйте только то, что нужно:

```php
class ViewerRole implements RoleInterface
{
    public function permissions(): array
    {
        return [
            PostsPermission::View,
            CommentsPermission::View,
        ];
    }
}
```

## Разделение по доменам

Группируйте разрешения по доменам, а не по действиям:

```php
// ✅ Правильно — домен-первичен
enum PostsPermission: string { case View, Create, Edit, Delete }
enum CommentsPermission: string { case View, Create, Moderate, Delete }

// ❌ Неправильно — действие-первично
enum ViewPermission: string { case Posts, Comments, Users }
```

## Именование ролей

```php
// ✅ Именуйте по бизнес-функции
class EditorRole {}
class ReviewerRole {}
class BillingManagerRole {}

// ❌ Не по уровню доступа
class Level2Role {}
class FullAccessRole {}
```

## Версионирование прав

Поскольку права — это enum-кейсы в коде, переименование требует миграции:

```php
// Добавьте deprecated-кейс для плавного перехода
enum PostsPermission: string implements PermissionInterface
{
    case Edit = 'posts.edit';
    /** @deprecated Используйте Edit */
    case Modify = 'posts.modify'; // алиас на время перехода
}
```
