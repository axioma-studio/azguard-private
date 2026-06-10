<?php

use AzGuard\Guard\DiscoveryService;
use AzGuard\Tests\Stubs\Roles\ManagerRole;
use AzGuard\Tests\Stubs\Roles\ProjectEditorRole;

test('it can discover role classes in a directory', function () {
    $service = new DiscoveryService;

    // Указываем путь к нашим тестовым заглушкам
    $path = __DIR__.'/../Stubs/Roles';
    $namespace = 'AzGuard\\Tests\\Stubs\\Roles\\';

    $discovered = $service->discoverRoles($path, $namespace);

    expect($discovered)->toBeArray()
        ->and($discovered)->toContain(ManagerRole::class)
        ->and($discovered)->toContain(ProjectEditorRole::class)
        ->and($discovered)->not->toContain('AzGuard\\Tests\\Stubs\\Roles\\InvalidRole');
});

test('it returns empty array for non-existent directory', function () {
    $service = new DiscoveryService;

    $discovered = $service->discoverRoles('/non/existent/path', 'App\\');

    expect($discovered)->toBeArray()->toBeEmpty();
});
