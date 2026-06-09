# Database Seeding

This guide covers how to seed roles and permissions — both static (code-defined) roles and dynamic (DB-backed) roles.

## Static roles don't need seeding

Roles defined as PHP classes are **always available** without seeding. You can assign them to users immediately after running migrations:

```php
// In a seeder, factory, or anywhere after `php artisan migrate`
$user->assignRole('editor');  // works out of the box
```

## Seeding dynamic roles

If you use `DynamicRole` for runtime-configurable roles, seed them in a dedicated seeder:

```php
// database/seeders/RolesSeeder.php
use AzGuard\Models\DynamicRole;
use App\AzGuard\App\Permissions\DocumentsPermission;
use App\AzGuard\App\Permissions\ReportsPermission;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $editor = DynamicRole::firstOrCreate(
            ['name' => 'editor', 'panel' => 'app'],
            ['level' => 10]
        );
        $editor->syncPermissions([
            DocumentsPermission::View,
            DocumentsPermission::Create,
            DocumentsPermission::Edit,
        ]);

        $viewer = DynamicRole::firstOrCreate(
            ['name' => 'viewer', 'panel' => 'app'],
            ['level' => 1]
        );
        $viewer->syncPermissions([
            DocumentsPermission::View,
        ]);

        $manager = DynamicRole::firstOrCreate(
            ['name' => 'manager', 'panel' => 'app'],
            ['level' => 50]
        );
        $manager->syncPermissions([
            DocumentsPermission::View,
            DocumentsPermission::Create,
            DocumentsPermission::Edit,
            DocumentsPermission::Delete,
            ReportsPermission::View,
            ReportsPermission::Export,
        ]);
    }
}
```

Register in `DatabaseSeeder`:

```php
public function run(): void
{
    $this->call([
        RolesSeeder::class,
        UsersSeeder::class,
    ]);
}
```

## Seeding users with roles

```php
// database/seeders/UsersSeeder.php
class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin User', 'password' => Hash::make('password')]
        );
        $admin->assignRole('super-admin', panel: 'admin');

        $editor = User::firstOrCreate(
            ['email' => 'editor@example.com'],
            ['name' => 'Editor User', 'password' => Hash::make('password')]
        );
        $editor->assignRole('editor', panel: 'app');

        $viewer = User::firstOrCreate(
            ['email' => 'viewer@example.com'],
            ['name' => 'Viewer User', 'password' => Hash::make('password')]
        );
        $viewer->assignRole('viewer', panel: 'app');
    }
}
```

## Seeding with factories

```php
// database/factories/UserFactory.php
public function editor(): static
{
    return $this->afterCreating(fn (User $u) =>
        $u->assignRole('editor', panel: 'app')
    );
}

public function manager(): static
{
    return $this->afterCreating(fn (User $u) =>
        $u->assignRole('manager', panel: 'app')
    );
}

public function admin(): static
{
    return $this->afterCreating(fn (User $u) =>
        $u->assignRole('super-admin', panel: 'admin')
    );
}
```

```php
// In tests or seeders
$editor  = User::factory()->editor()->create();
$manager = User::factory()->manager()->create();
$admin   = User::factory()->admin()->create();
```

## Idempotent seeders

Always use `firstOrCreate` / `updateOrCreate` so seeders can be re-run safely:

```php
// ✅ Idempotent — safe to run multiple times
$role = DynamicRole::firstOrCreate(
    ['name' => 'editor', 'panel' => 'app'],
    ['level' => 10]
);

// ❌ Creates duplicates on re-seed
$role = DynamicRole::create(['name' => 'editor', ...]);
```

## Syncing static roles to the DB

After deploying new PHP role classes, sync them to `az_guard_roles` so Filament dropdowns and API endpoints stay up to date:

```bash
# Sync all panels
php artisan azguard:sync-roles

# Sync a specific panel
php artisan azguard:sync-roles --panel=app
```

This command is safe to include in your CI/CD deploy pipeline.

## Running seeders in CI

```bash
php artisan migrate:fresh --seed
php artisan db:seed --class=RolesSeeder
```

For test environments that use `RefreshDatabase`, static roles are always available without seeding. Only seed if you specifically need dynamic roles or specific user fixtures.
