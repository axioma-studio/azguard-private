# Каталог разрешений

Каталог разрешений — это реестр всех enum-классов разрешений вашего приложения. AzGuard строит его из enum'ов, зарегистрированных на панелях.

## Структура каталога

```
app/AzGuard/
├── App/                    ← панель 'app'
│   ├── AppPanelProvider.php
│   ├── Permissions/
│   │   ├── PostsPermission.php
│   │   ├── CommentsPermission.php
│   │   └── ReportsPermission.php
│   └── Roles/
│       ├── EditorRole.php
│       └── ViewerRole.php
├── Admin/                  ← панель 'admin'
│   ├── AdminPanelProvider.php
│   ├── Permissions/
│   │   └── UsersPermission.php
│   └── Roles/
│       └── AdminRole.php
└── Api/                    ← панель 'api'
    ├── ApiPanelProvider.php
    ├── Permissions/
    │   └── ApiPermission.php
    └── Roles/
        └── ApiConsumerRole.php
```

## Регистрация на панели

```php
// app/AzGuard/App/AppPanelProvider.php
use AzGuard\PanelProvider;
use AzGuard\Support\Panel;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->permissionEnums([
                PostsPermission::class,
                CommentsPermission::class,
                ReportsPermission::class,
            ])
            ->roleClasses([
                EditorRole::class,
                ViewerRole::class,
            ]);
    }
}
```

## Просмотр каталога

```bash
php artisan guard:catalog
php artisan guard:list-permissions
```
