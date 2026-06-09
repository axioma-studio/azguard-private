# Роли vs Разрешения

## Когда использовать роли

Роль — это **набор разрешений**, объединённых бизнес-логикой. Назначайте роли, когда:

- Группа пользователей делает одно и то же (редакторы, модераторы, менеджеры)
- Набор прав меняется вместе (добавили фичу — добавили в роль)
- Нужно проверить принадлежность к группе (`hasRole('editor')`)

```php
// ✅ Хорошо — роль отражает бизнес-роль
$user->assignRole(EditorRole::class);

// ❌ Плохо — 15 прямых грантов вместо одной роли
$user->grantPermission(PostsPermission::View);
$user->grantPermission(PostsPermission::Create);
// ...
```

## Когда использовать прямые гранты

Прямой грант — исключение из роли. Используйте, когда:

- Нужен **временный** доступ (с TTL)
- Один пользователь должен получить право, которое не входит в его роль
- Нужно перекрыть доступ без изменения роли

## Принцип минимальных прав

```php
// ✅ Хорошо — каждая роль содержит только нужные права
class ViewerRole implements RoleInterface
{
    public function permissions(): array
    {
        return [PostsPermission::View, CommentsPermission::View];
    }
}

// ❌ Плохо — роль «на всякий случай» с лишними правами
class ViewerRole implements RoleInterface
{
    public function permissions(): array
    {
        return [
            PostsPermission::View,
            PostsPermission::Edit,   // Зачем?
            PostsPermission::Delete, // Точно не нужно
        ];
    }
}
```

## Именование

- Роли: существительные, отражающие бизнес-роль — `EditorRole`, `ModeratorRole`, `BillingManagerRole`
- Разрешения: `{Ресурс}Permission` — `PostsPermission`, `InvoicesPermission`
- Кейсы: глагол или глагол+существительное — `View`, `Create`, `ExportToPdf`
