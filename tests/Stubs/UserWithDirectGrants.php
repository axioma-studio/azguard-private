<?php

namespace AzGuard\Tests\Stubs;

use AzGuard\Concerns\HasDirectGrants;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * User-стаб с подключённым HasDirectGrants для тестов DirectGrant.
 * Наследует Stubs\User, чтобы factory() работал через Orchestra Testbench.
 */
class UserWithDirectGrants extends User
{
    use HasDirectGrants;

    protected $table = 'users';

    protected static function newFactory(): Factory
    {
        return new class extends Factory
        {
            protected $model = UserWithDirectGrants::class;

            public function definition(): array
            {
                return [
                    'name' => fake()->name(),
                    'email' => fake()->unique()->safeEmail(),
                    'password' => 'password',
                ];
            }
        };
    }
}
