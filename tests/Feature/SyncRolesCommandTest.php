<?php

declare(strict_types=1);

use AzGuard\Facades\AzGuard;
use AzGuard\Models\Role;
use AzGuard\Support\Panel;

it('creates roles for panel from PHP role classes', function (): void {
    AzGuard::registerPanel(
        panel: Panel::make()->id(id: 'test')->label(label: 'Test Panel'),
    );

    // Ensure roles table is empty
    expect(Role::query()->count())->toBe(0);

    $this->artisan('azguard:sync-roles')
        ->expectsOutputToContain('Синхронизация завершена')
        ->assertSuccessful();

    expect(Role::query()->count())->toBeGreaterThan(0);
});

it('filters by panel option', function (): void {
    AzGuard::registerPanel(
        panel: Panel::make()->id(id: 'test')->label(label: 'Test Panel'),
    );

    $this->artisan('azguard:sync-roles', ['--panel' => 'test'])
        ->expectsOutputToContain('Синхронизация завершена')
        ->assertSuccessful();
});

it('supports dry-run mode without writing to database', function (): void {
    AzGuard::registerPanel(
        panel: Panel::make()->id(id: 'test')->label(label: 'Test Panel'),
    );

    // Имитация существующей роли, чтобы команда что-то нашла
    Role::query()->create([
        'name' => 'existing-role',
        'level' => 10,
        'class_name' => 'AzGuard\\Tests\\Stubs\\Roles\\ExistingRole',
    ]);

    $beforeCount = Role::query()->count();

    $this->artisan('azguard:sync-roles', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Изменения не будут записаны в БД.')
        ->expectsOutputToContain('Синхронизация завершена (dry-run)')
        ->assertSuccessful();

    $afterCount = Role::query()->count();

    expect($afterCount)->toBe($beforeCount);
});
