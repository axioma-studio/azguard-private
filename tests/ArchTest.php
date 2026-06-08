<?php

declare(strict_types=1);

/**
 * Architecture tests для AzGuard.
 * Проверяют соответствие структуры кода архитектурным соглашениям.
 */

arch('no debugging calls in source')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed()
    ->ignoring('AzGuard\\Tests');

arch('contracts are interfaces')
    ->expect('AzGuard\\Contracts')
    ->toBeInterfaces();

arch('models extend Eloquent Model')
    ->expect('AzGuard\\Models')
    ->toExtend(\Illuminate\Database\Eloquent\Model::class);

arch('service provider extends ServiceProvider')
    ->expect('AzGuard\\AzGuardServiceProvider')
    ->toExtend(\Illuminate\Support\ServiceProvider::class);

arch('roles implement RoleInterface')
    ->expect('AzGuard\\Roles')
    ->toImplement(\AzGuard\Contracts\RoleInterface::class)
    ->ignoring('AzGuard\\Roles\\BaseRole');

arch('facades extend Illuminate Facade')
    ->expect('AzGuard\\Facades')
    ->toExtend(\Illuminate\Support\Facades\Facade::class);

arch('strict types declared in all source files')
    ->expect('AzGuard')
    ->toUseStrictTypes()
    ->ignoring([
        'AzGuard\\Tests',
        'AzGuard\\Concerns\\HasAzGuard',  // trait — legacy, будет обновлён
    ]);

arch('commands extend Illuminate Console Command')
    ->expect('AzGuard\\Commands')
    ->toExtend(\Illuminate\Console\Command::class)
    ->ignoring('AzGuard\\Commands\\Concerns');
