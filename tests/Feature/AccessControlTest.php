<?php

namespace AzGuard\Tests\Feature;

use App\Models\User;
use App\Models\Post; // Убедись, что модель существует в тестах или создай заглушку
use AzGuard\Facades\AzGuard;
use AzGuard\Tests\TestCase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Имитируем регистрацию панели "admin"
        config(['az-guard.panels' => [
            \AzGuard\Tests\Stubs\TestAdminPanelProvider::class,
        ]]);

        // Перезагружаем провайдеры, чтобы сработал boot() нашего PanelProvider
        (new \AzGuard\Tests\Stubs\TestAdminPanelProvider($this->app))->boot();
    }

    /** @test */
    public function it_can_authorize_user_using_panel_scoped_permissions()
    {
        // 2. Создаем пользователя
        $user = User::factory()->create();

        // 3. Имитируем наличие у пользователя права с префиксом панели
        // В реальной системе это будет проверка через твой HasAzPermissions трейт
        // Для теста мы можем "подменить" проверку
        $user->setPermissions(['admin.post.view']);

        // 4. Проверяем, что политика зарегистрирована и работает
        // Laravel должен понять, что для Post нужно вызвать TestPostPolicy
        $post = new Post();

        expect($user->can('view', $post))->toBeTrue();
        expect($user->can('create', $post))->toBeFalse();
    }
}
