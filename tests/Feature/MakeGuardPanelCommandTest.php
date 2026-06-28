<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    File::deleteDirectory(directory: base_path('app/Guards'));
    File::deleteDirectory(directory: base_path('Modules'));
});

it('создаёт guard-панель с доменной структурой', function (): void {
    $this->artisan(
        command: 'make:guard-panel',
        parameters: [
            'panel' => 'Admin',
            'domain' => 'Documents',
            '--role' => 'SuperAdmin',
        ],
    )->assertSuccessful();

    $basePath = base_path('app/Guards/Admin');

    expect($basePath.'/AdminGuardPanelProvider.php')->toBeFile()
        ->and($basePath.'/Roles/SuperAdminRole.php')->toBeFile()
        ->and($basePath.'/Documents/Permissions/DocumentsPermission.php')->toBeFile()
        ->and($basePath.'/Documents/Policies/DocumentsPolicy.php')->toBeFile();

    $policyContent = File::get(path: $basePath.'/Documents/Policies/DocumentsPolicy.php');
    expect($policyContent)->toContain('namespace App\Guards\Admin\Documents\Policies;')
        ->and($policyContent)->toContain('use AuthorizesPermission;')
        ->and($policyContent)->toContain('#[GuardPolicy');
});

it('создаёт Abilities при флаге --with-abilities', function (): void {
    $this->artisan(
        command: 'make:guard-panel',
        parameters: [
            'panel' => 'BlogAdmin',
            'domain' => 'Posts',
            '--path' => 'Modules/Blog/Guards',
            '--with-abilities' => true,
        ],
    )->assertSuccessful();

    expect(base_path('Modules/Blog/Guards/BlogAdmin/Posts/Abilities/PostsAbilities.php'))->toBeFile();
});

it('auto-registers the generated panel provider in config/az-guard.php', function (): void {
    $configPath = config_path('az-guard.php');
    $backup = File::exists($configPath) ? File::get($configPath) : null;

    File::ensureDirectoryExists(dirname($configPath));
    File::put($configPath, "<?php\n\nreturn [\n    'panels' => [],\n];\n");

    $this->artisan(
        command: 'make:guard-panel',
        parameters: ['panel' => 'Admin', 'domain' => 'Documents'],
    )->assertSuccessful();

    expect(File::get($configPath))
        ->toContain('App\Guards\Admin\AdminGuardPanelProvider::class');

    $backup === null ? File::delete($configPath) : File::put($configPath, $backup);
});

it('отказывается если панель уже существует', function (): void {
    $path = base_path('app/Guards/ExistingPanel');
    File::makeDirectory(path: $path, mode: 0755, recursive: true);

    $this->artisan(
        command: 'make:guard-panel',
        parameters: ['panel' => 'ExistingPanel', 'domain' => 'Docs'],
    )
        ->assertFailed();
});
