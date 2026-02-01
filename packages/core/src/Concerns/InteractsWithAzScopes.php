<?php

namespace AzGuard\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait InteractsWithAzScopes
{
    public static function bootInteractsWithAzScopes()
    {
        static::addGlobalScope('az_guard_filter', function (Builder $builder) {
            if (app()->runningInConsole() || ! auth()->check()) {
                return;
            }
            $user = auth()->user();
            if (! method_exists($user, 'azScopes')) {
                return;
            }
            $scopes = $user->azScopes()->where('scope_entity_type', static::class)
                ->orWhere(fn ($q) => $q->whereNull('scope_entity_type'))->get();
            foreach ($scopes as $s) {
                if (class_exists($s->scope_class)) {
                    app($s->scope_class)->apply($builder, $user, $s->scope_entity);
                }
            }
        });
    }
}
