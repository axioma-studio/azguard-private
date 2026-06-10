<?php

declare(strict_types=1);
use AzGuard\Contracts\RoleInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

arch()->preset()->php()->ignoring('AzGuard\\Filament');

arch()->preset()->security()->ignoring('AzGuard\\Filament');

arch('no debugging calls in source')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed()
    ->ignoring('AzGuard\\Tests');

arch('contracts are interfaces')
    ->expect('AzGuard\\Contracts')
    ->toBeInterfaces();

arch('models extend Eloquent Model')
    ->expect('AzGuard\\Models')
    ->toExtend(Model::class);

arch('service provider extends ServiceProvider')
    ->expect('AzGuard\\AzGuardServiceProvider')
    ->toExtend(ServiceProvider::class);

arch('roles implement RoleInterface')
    ->expect('AzGuard\\Roles')
    ->toImplement(RoleInterface::class)
    ->ignoring('AzGuard\\Roles\\BaseRole');

arch('facades extend Illuminate Facade')
    ->expect('AzGuard\\Facades')
    ->toExtend(Facade::class);

arch('strict types declared in all source files')
    ->expect('AzGuard')
    ->toUseStrictTypes()
    ->ignoring([
        'AzGuard\\Tests',
    ]);

arch('commands extend Illuminate Console Command')
    ->expect('AzGuard\\Commands')
    ->toExtend(Command::class)
    ->ignoring('AzGuard\\Commands\\Concerns');
