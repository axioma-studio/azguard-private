<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    // В Testbench base_path указывает на виртуальную структуру в vendor/orchestra
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

    $basePath = base_path('app/Guards/Admin');

    // Проверяем существование
    expect($basePath . '/AdminGuardPanelProvider.php')->toBeFile();
    expect($basePath . '/Roles/SuperAdminRole.php')->toBeFile();

    // Проверяем содержимое и Namespace
    $roleContent = File::get($basePath . '/Roles/SuperAdminRole.php');
    expect($roleContent)->toContain('namespace App\Guards\Admin\Roles;')
        ->and($roleContent)->toContain('class SuperAdminRole extends BaseRole');

    $policyContent = File::get($basePath . '/Policies/UserPolicy.php');
    expect($policyContent)->toContain('namespace App\Guards\Admin\Policies;')
        ->and($policyContent)->toContain("hasAzPermission('user.view')");
});

it('can generate a guard panel for a module with custom namespace', function () {
    $this->artisan('make:guard-panel')
        ->expectsQuestion('Укажите путь для создания (например, app/Guards или Modules/Blog/Guards)', 'Modules/Blog/Guards')
        ->expectsQuestion('Назовите панель (например, Admin)', 'BlogAdmin')
        ->expectsQuestion('Название первой роли', 'Manager')
        ->expectsQuestion('Название маппинга разрешений (ресурса)', 'Post')
        ->assertExitCode(0);

    $basePath = base_path('Modules/Blog/Guards/BlogAdmin');

    // Проверяем неймспейс для модуля (БЕЗ App в начале)
    $policyContent = File::get($basePath . '/Policies/PostPolicy.php');

    // Вот та самая проверка, которую мы обсуждали
    expect($policyContent)->toContain('namespace Modules\Blog\Guards\BlogAdmin\Policies;');
});

it('fails when panel already exists', function () {
    $path = base_path('app/Guards/ExistingPanel');
    File::makeDirectory($path, 0755, true);

    $this->artisan('make:guard-panel')
        ->expectsQuestion('Укажите путь для создания (например, app/Guards или Modules/Blog/Guards)', 'app/Guards')
        ->expectsQuestion('Назовите панель (например, Admin)', 'ExistingPanel')
        ->expectsOutputToContain('Ошибка: Панель по пути')
        ->assertExitCode(0);
});
