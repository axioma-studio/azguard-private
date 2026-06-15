# Сидирование базы данных

## Базовый сидер

```php
// database/seeders/RolesAndPermissionsSeeder.php
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Синхронизируем классы ролей с БД (создаёт записи по имени роли)
        Artisan::call('guard:sync-roles');

        // Назначаем роли по имени конкретным пользователям
        $admin = User::where('email', 'admin@example.com')->first();
        $admin?->assignRole('admin');

        $editor = User::where('email', 'editor@example.com')->first();
        $editor?->assignRole('editor');
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
        $user->assignRole('editor');
    });
}

// В тесте
$editor = User::factory()->editor()->create();
```
