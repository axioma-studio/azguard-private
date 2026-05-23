<?php

use AzGuard\Models\Role;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;

// Создаем "фейковый" класс роли для теста, чтобы не зависеть от генератора
class FakeAdminRole
{
    public function permissions(): array
    {
        return ['admin.post.view', 'admin.post.edit'];
    }
}

class FakePost {}

// Создаем "фейковую" политику
class FakePostPolicy
{
    public function view($user)
    {
        return $user->hasAzPermission('admin.post.view');
    }
}

it('grants access when user has a role with the required permission', function () {
    Gate::policy(FakePost::class, FakePostPolicy::class);

    // 2. Создаем роль в БД и указываем путь к классу логики
    $role = Role::create([
        'name' => 'Administrator',
        'class_name' => FakeAdminRole::class,
    ]);

    // 3. Создаем пользователя и привязываем роль (используя твой трейт HasAzGuard)
    $user = \AzGuard\Tests\Stubs\User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
    $user->roles()->attach($role);

    // 4. Проверка: HasAzGuard должен залезть в FakeAdminRole и найти там разрешение
    expect($user->hasAzPermission('admin.post.view'))->toBeTrue();
    expect($user->hasAzPermission('admin.post.delete'))->toBeFalse();

    $this->actingAs($user);

    expect($user->can('view', new FakePost))->toBeTrue();
});
