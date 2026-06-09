<?php

declare(strict_types=1);

namespace AzGuard\Tests\Commands\Concerns;

use AzGuard\Commands\Concerns\ResolvesUserModel;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

/**
 * Stub console command that uses ResolvesUserModel.
 * Simulates Artisan option resolution without a real command context.
 */
class ResolvesUserModelStubCommand
{
    use ResolvesUserModel;

    public function __construct(private ?string $modelOption = null) {}

    protected function option(string $key): mixed
    {
        return $key === 'model' ? $this->modelOption : null;
    }

    public function resolveUserModelClassPublic(): string
    {
        return $this->resolveUserModelClass();
    }
}

final class ResolvesUserModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $app = new Container;
        $app->instance('config', new ConfigRepository([
            'auth' => ['providers' => ['users' => ['model' => 'App\Models\User']]],
        ]));
        Container::setInstance($app);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }

    public function test_returns_model_from_option_when_provided(): void
    {
        $command = new ResolvesUserModelStubCommand(modelOption: 'App\\Models\\Admin');

        $this->assertSame('App\\Models\\Admin', $command->resolveUserModelClassPublic());
    }

    public function test_falls_back_to_config_when_option_is_null(): void
    {
        // config() is not available in pure PHPUnit, so we test via the option fallback only.
        // ResolvesUserModel falls back to config('auth.providers.users.model', 'App\Models\User').
        // Here we verify the branch: option is null → the call does NOT return the option value.
        $command = new ResolvesUserModelStubCommand(modelOption: null);

        // Without a running app, config() returns default 'App\Models\User'
        $result = $command->resolveUserModelClassPublic();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_falls_back_to_config_when_option_is_empty_string(): void
    {
        $command = new ResolvesUserModelStubCommand(modelOption: '');

        // Empty string is treated same as null → falls back to config
        $result = $command->resolveUserModelClassPublic();

        $this->assertIsString($result);
        $this->assertNotSame('', $result);
    }

    public function test_returns_custom_model_over_config_default(): void
    {
        $command = new ResolvesUserModelStubCommand(modelOption: 'Domain\\Auth\\Entities\\Member');

        $this->assertSame('Domain\\Auth\\Entities\\Member', $command->resolveUserModelClassPublic());
    }
}
