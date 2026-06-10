<?php

namespace AzGuard\Tests\Stubs;

use AzGuard\Concerns\HasAzGuard;
use AzGuard\Concerns\HasScopedRoles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasAzGuard;
    use HasFactory;
    use HasScopedRoles;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function newFactory(): Factory
    {
        return new class extends Factory
        {
            protected $model = User::class;

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
