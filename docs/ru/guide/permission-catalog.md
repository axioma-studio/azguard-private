# Каталог разрешений

Каталог разрешений — это реестр всех enum-классов разрешений вашего приложения. AzGuard читает его при инициализации.

## Структура каталога

```
app/AzGuard/
├── App/                    ← панель 'app'
│   ├── AppPanel.php
│   ├── Permissions/
│   │   ├── PostsPermission.php
│   │   ├── CommentsPermission.php
│   │   └── ReportsPermission.php
│   └── Roles/
│       ├── EditorRole.php
│       └── ViewerRole.php
├── Admin/                  ← панель 'admin'
│   ├── AdminPanel.php
│   ├── Permissions/
│   │   └── UsersPermission.php
│   └── Roles/
│       └── AdminRole.php
└── Api/                    ← панель 'api'
    ├── ApiPanel.php
    ├── Permissions/
    │   └── ApiPermission.php
    └── Roles/
        └── ApiConsumerRole.php
```

## Регистрация в Panel

```php
// app/AzGuard/App/AppPanel.php
class AppPanel implements PanelInterface
{
    public function getName(): string { return 'app'; }

    public function getPermissions(): array
    {
        return [
            PostsPermission::class,
            CommentsPermission::class,
            ReportsPermission::class,
        ];
    }

    public function getRoles(): array
    {
        return [
            EditorRole::class,
            ViewerRole::class,
        ];
    }
}
```

## Просмотр каталога

```bash
php artisan azguard:list-permissions
php artisan azguard:list-roles
```
