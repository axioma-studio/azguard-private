# Расширение

## Кастомный драйвер хранилища

```php
use AzGuard\Contracts\PermissionStorageInterface;

class RedisPermissionStorage implements PermissionStorageInterface
{
    public function getUserPermissions(int $userId, string $panel): array
    {
        return Cache::tags(['azguard', "user:{$userId}"])
            ->remember("permissions:{$panel}", 300, function () use ($userId, $panel) {
                return DB::table('azguard_user_roles')
                    ->where('user_id', $userId)
                    ->where('panel', $panel)
                    ->pluck('role_class')
                    ->flatMap(fn ($class) => (new $class)->permissions())
                    ->toArray();
            });
    }
}
```

```php
// AppServiceProvider
public function register(): void
{
    $this->app->bind(PermissionStorageInterface::class, RedisPermissionStorage::class);
}
```

## Кастомный Guard

```php
use AzGuard\Contracts\GuardInterface;

class TenantGuard implements GuardInterface
{
    public function check(User $user, string $permission, array $context = []): bool
    {
        $tenantId = $context['tenant_id'] ?? null;
        // Дополнительная проверка по тенанту
        return $user->hasPermission($permission) && $this->tenantAllows($user, $tenantId);
    }
}
```

## Свои Artisan-команды поверх AzGuard

```php
class ImportRolesCommand extends Command
{
    protected $signature = 'roles:import {file}';

    public function handle(): void
    {
        $data = json_decode(File::get($this->argument('file')), true);
        foreach ($data['users'] as $item) {
            $user = User::findOrFail($item['id']);
            $user->syncRoles(array_map(
                fn ($r) => "App\\AzGuard\\App\\Roles\\{$r}Role",
                $item['roles']
            ));
        }
        $this->info('Роли импортированы.');
    }
}
```
