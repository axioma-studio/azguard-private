<?php

declare(strict_types=1);

arch('filament plugin implements Filament Plugin contract')
    ->expect('AzGuard\\Filament\\AzGuardPlugin')
    ->toImplement(\Filament\Contracts\Plugin::class);

arch('filament service provider extends ServiceProvider')
    ->expect('AzGuard\\Filament\\AzGuardFilamentServiceProvider')
    ->toExtend(\Illuminate\Support\ServiceProvider::class);

arch('no debugging calls in filament package')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed()
    ->ignoring('AzGuard\\Tests');

arch('filament resources extend Filament Resource')
    ->expect('AzGuard\\Filament\\Resources')
    ->toExtend(\Filament\Resources\Resource::class)
    ->ignoring([
        'AzGuard\\Filament\\Resources\\RoleResource\\Pages',
        'AzGuard\\Filament\\Resources\\RoleResource\\RelationManagers',
        'AzGuard\\Filament\\Resources\\DirectGrantResource\\Pages',
        'AzGuard\\Filament\\Resources\\AzGuardResource',
        'AzGuard\\Filament\\Resources\\GuardResource',
    ]);

arch('filament relation managers extend RelationManager')
    ->expect('AzGuard\\Filament\\Resources\\RoleResource\\RelationManagers')
    ->toExtend(\Filament\Resources\RelationManagers\RelationManager::class);
