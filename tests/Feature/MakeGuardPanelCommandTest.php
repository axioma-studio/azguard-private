<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Очищаем тестовые директории перед каждым тестом
    File::deleteDirectory(base_path('app/Guards'));
    File::deleteDirectory(base_path('Modules'));
});

it('can generate a guard panel with default path', function () {
    $this->artisan('make:guard-panel')
        ->expectsQuestion('Укажите путь для создания (например, app/Guards или Modules/Blog/Guards)', 'app/Guards')
        ->expectsQuestion('Назовите панель (например, Admin)', 'Admin')
        ->expectsQuestion('Название первой роли', 'SuperAdmin')
        ->expectsQuestion('Название маппинга разрешений (ресурса)', 'User')
        ->assertExitCode(0);

    // Проверяем структуру папок
    expect(base_path('app/Guards/Admin/Roles'))->toBeDirectory();
    expect(base_path('app/Guards/Admin/Policies'))->toBeDirectory();

    // Проверяем содержимое файлов и неймспейсы
    $roleContent = File::get(base_path('app/Guards/Admin/Roles/SuperAdminRole.php'));
    expect($roleContent)->toContain('namespace App\Guards\Admin\Roles;')
        ->and($roleContent)->toContain('class SuperAdminRole extends BaseRole');

    $providerContent = File::get(base_path('app/Guards/Admin/AdminGuardPanelProvider.php'));
    expect($providerContent)->toContain('namespace App\Guards\Admin;');
});

it('can generate a guard panel for a module with custom namespace', function () {
    $this->artisan('make:guard-panel')
        ->expectsQuestion('Укажите путь для создания (например, app/Guards или Modules/Blog/Guards)', 'Modules/Blog/Guards')
        ->expectsQuestion('Назовите панель (например, Admin)', 'BlogAdmin')
        ->expectsQuestion('Название первой роли', 'Manager')
        ->expectsQuestion('Название маппинга разрешений (ресурса)', 'Post')
        ->assertExitCode(0);

    // Проверяем неймспейс для модуля
    $policyContent = File::get(base_path('Modules/Blog/Guards/BlogAdmin/Policies/PostPolicy.php'));
    expect($policyContent)->toContain('namespace Modules\Blog\Guards\BlogAdmin\Policies;');
});

it('fails when panel already exists', function () {
    // Создаем директорию заранее
    $path = base_path('app/Guards/ExistPanel');
    File::makeDirectory($path, 0755, true);

    $this->artisan('make:guard-panel')
        ->expectsQuestion('Укажите путь для создания (например, app/Guards или Modules/Blog/Guards)', 'app/Guards')
        ->expectsQuestion('Назовите панель (например, Admin)', 'ExistPanel')
        ->expectsOutput('Ошибка: Панель по пути [' . $path . '] уже существует!')
        ->assertExitCode(0);
});
