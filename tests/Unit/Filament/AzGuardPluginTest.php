<?php

declare(strict_types=1);

use AzGuard\Filament\AzGuardPlugin;
use AzGuard\Filament\Resources\DirectGrantResource;
use AzGuard\Filament\Resources\RoleResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

it('implements Filament Plugin contract', function () {
    expect(AzGuardPlugin::make())->toBeInstanceOf(Plugin::class);
});

it('returns correct plugin id', function () {
    expect(AzGuardPlugin::make()->getId())->toBe('az-guard');
});

it('make() returns new instance each time', function () {
    $a = AzGuardPlugin::make();
    $b = AzGuardPlugin::make();

    expect($a)->not->toBe($b);
});

it('defaults panelId to app', function () {
    expect(AzGuardPlugin::make()->getPanelId())->toBe('app');
});

it('forPanel() sets panelId and returns same instance', function () {
    $plugin = AzGuardPlugin::make();
    $result = $plugin->forPanel('admin');

    expect($result)->toBe($plugin)
        ->and($plugin->getPanelId())->toBe('admin');
});

it('forPanel() accepts arbitrary panel ids', function (string $panelId) {
    expect(AzGuardPlugin::make()->forPanel($panelId)->getPanelId())->toBe($panelId);
})->with(['admin', 'tenant', 'super-admin', 'app']);

it('register() injects RoleResource and DirectGrantResource into panel', function () {
    $registered = [];

    $panel = Mockery::mock(Panel::class);
    $panel->shouldReceive('resources')
        ->once()
        ->withArgs(function (array $resources) use (&$registered) {
            $registered = $resources;

            return true;
        })
        ->andReturnSelf();
    $panel->shouldReceive('pages')->once()->andReturnSelf();

    AzGuardPlugin::make()->register($panel);

    expect($registered)->toContain(RoleResource::class)
        ->toContain(DirectGrantResource::class);
});

it('boot() runs without exception', function () {
    $panel = Mockery::mock(Panel::class);

    expect(fn () => AzGuardPlugin::make()->boot($panel))->not->toThrow(Throwable::class);
});
