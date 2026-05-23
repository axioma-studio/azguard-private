<?php

declare(strict_types=1);

use AzGuard\Attributes\CheckPermission;
use AzGuard\Attributes\SkipGuardCheck;
use AzGuard\Facades\AzGuard;
use AzGuard\Http\Middleware\CheckAccess;
use AzGuard\Support\Panel;
use AzGuard\Tests\Stubs\Posts\Permissions\PostPermission;
use AzGuard\Tests\Stubs\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

it('пропускает invokable-контроллер с CheckPermission', function (): void {
    AzGuard::setCurrentPanel(panel: Panel::make()->id(id: 'test'));

    Gate::define('test.post.view', fn (User $user): bool => true);

    Route::middleware(['web', CheckAccess::class])
        ->get('/azguard-invoke-test', InvokablePostController::class);

    $user = new User;
    $user->id = 1;

    $this->actingAs(user: $user)
        ->get(uri: '/azguard-invoke-test')
        ->assertSuccessful();
});

it('пропускает метод с SkipGuardCheck без Gate', function (): void {
    Route::middleware(['web', CheckAccess::class])
        ->get('/azguard-skip-test', [SkipGuardController::class, 'show']);

    $this->get(uri: '/azguard-skip-test')
        ->assertSuccessful();
});

final class InvokablePostController
{
    #[CheckPermission(permission: PostPermission::View)]
    public function __invoke(): string
    {
        return 'ok';
    }
}

final class SkipGuardController
{
    #[SkipGuardCheck]
    public function show(): string
    {
        return 'ok';
    }
}
