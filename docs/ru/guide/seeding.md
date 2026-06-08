# Сидирование базы данных

## Базовый сидер

```php
// database/seeders/RolesAndPermissionsSeeder.php
use App\AzGuard\App\Roles\{EditorRole, ViewerRole};
use App\AzGuard\Admin\Roles\AdminRole;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Синхронизируем роли с БД
        Artisan::call('azguard:sync-roles');

        // Назначаем роли конкретным пользователям
        $admin = User::where('email', 'admin@example.com')->first();
        $admin?->assignRole(AdminRole::class);

        $editor = User::where('email', 'editor@example.com')->first();
        $editor?->assignRole(EditorRole::class);
    }
}
```

## Сидирование в тестах

```php
public function test_seeder_assigns_correct_roles(): void
{
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::where('email', 'admin@example.com')->first();
    $this->assertTrue($admin->hasRole('admin'));
    $this->assertTrue($admin->hasPermission(UsersPermission::Manage));
}
```

## Фабрики с ролями

```php
// database/factories/UserFactory.php
public function editor(): static
{
    return $this->afterCreating(function (User $user) {
        $user->assignRole(EditorRole::class);
    });
}

// В тесте
$editor = User::factory()->editor()->create();
```
