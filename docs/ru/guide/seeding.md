# Сидирование БД

## Базовый сидер ролей

```php
// database/seeders/RoleSeeder.php
use AzGuard\Facades\AzGuard;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            EditorRole::class,
            ModeratorRole::class,
            ViewerRole::class,
        ];

        foreach ($roles as $roleClass) {
            AzGuard::registerRole($roleClass);
        }

        $this->command->info('Роли зарегистрированы: ' . count($roles));
    }
}
```

## Назначение ролей пользователям

```php
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Администратор
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->assignRole(AdminRole::class);

        // Редакторы
        User::factory(5)->create()->each(function ($user) {
            $user->assignRole(EditorRole::class);
        });

        // Читатели
        User::factory(20)->create()->each(function ($user) {
            $user->assignRole(ViewerRole::class);
        });
    }
}
```

## Идемпотентный сидер

```php
public function run(): void
{
    $admin = User::firstOrCreate(
        ['email' => 'admin@example.com'],
        User::factory()->make()->toArray(),
    );

    if (!$admin->hasRole(AdminRole::class)) {
        $admin->assignRole(AdminRole::class);
    }
}
```
