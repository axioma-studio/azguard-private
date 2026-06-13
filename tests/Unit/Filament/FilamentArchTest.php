<?php

declare(strict_types=1);
use Filament\Contracts\Plugin;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Support\ServiceProvider;

arch('filament plugin implements Filament Plugin contract')
    ->expect('AzGuard\\Filament\\AzGuardPlugin')
    ->toImplement(Plugin::class);

arch('filament service provider extends ServiceProvider')
    ->expect('AzGuard\\Filament\\AzGuardFilamentServiceProvider')
    ->toExtend(ServiceProvider::class);

arch('no debugging calls in filament package')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed()
    ->ignoring('AzGuard\\Tests');

arch('filament resources extend Filament Resource')
    ->expect('AzGuard\\Filament\\Resources')
    ->toExtend(Filament\Resources\Resource::class)
    ->ignoring([
        'AzGuard\\Filament\\Resources\\RoleResource\\Pages',
        'AzGuard\\Filament\\Resources\\RoleResource\\RelationManagers',
        'AzGuard\\Filament\\Resources\\DirectGrantResource\\Pages',
    ]);

arch('filament relation managers extend RelationManager')
    ->expect('AzGuard\\Filament\\Resources\\RoleResource\\RelationManagers')
    ->toExtend(RelationManager::class);
