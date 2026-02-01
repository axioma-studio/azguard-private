<?php

namespace AzGuard\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait InteractsWithAzScopes
{
    public static function bootInteractsWithAzScopes(): void
    {
        static::addGlobalScope('az_guard_filter', function (Builder $builder) {
            if (app()->runningInConsole() || !Auth::check()) {
                return;
            }

            $user = Auth::user();

            // Если у пользователя есть метод получения скоупов
            if (method_exists($user, 'azScopes')) {
                $scopes = $user->azScopes()
                    ->where('scope_entity_type', static::class)
                    ->get();

                foreach ($scopes as $scope) {
                    if (class_exists($scope->scope_class)) {
                        app($scope->scope_class)->apply($builder, $user, $scope->scope_entity);
                    }
                }
            }
        });
    }
}
