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

// Создаем "фейковую" политику
class FakePostPolicy
{
    public function view($user)
    {
        return $user->hasAzPermission('admin.post.view');
    }
}

it('grants access when user has a role with the required permission', function () {
    // 1. Настройка: связываем модель Post с нашей политикой
    Gate::policy(\App\Models\Post::class, FakePostPolicy::class);

    // 2. Создаем роль в БД и указываем путь к классу логики
    $role = Role::create([
        'name' => 'Administrator',
        'class_name' => FakeAdminRole::class,
    ]);

    // 3. Создаем пользователя и привязываем роль (используя твой трейт HasAzGuard)
    $user = \App\Models\User::factory()->create();
    $user->roles()->attach($role);

    // 4. Проверка: HasAzGuard должен залезть в FakeAdminRole и найти там разрешение
    expect($user->hasAzPermission('admin.post.view'))->toBeTrue();
    expect($user->hasAzPermission('admin.post.delete'))->toBeFalse();

    // 5. Проверка через стандартный Gate Laravel
    $post = new \App\Models\Post();
    expect($user->can('view', $post))->toBeTrue();
});
