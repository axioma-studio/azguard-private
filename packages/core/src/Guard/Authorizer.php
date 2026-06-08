<?php

declare(strict_types=1);

namespace AzGuard\Guard;

use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Основной компонент авторизации AzGuard.
 *
 * Регистрируется через Gate::before() и:
 * 1) Возвращает true для суперадмина (wildcard '*').
 * 2) Проверяет конкретное право $ability через HasAzGuard::hasAzPermission().
 * 3) Возвращает null (pass-through) если пользователь не использует HasAzGuard.
 */
final class Authorizer
{
    public function check(Authorizable $user, string $ability): ?bool
    {
        if (! method_exists($user, 'getAzPermissions')) {
            return null;
        }

        $permissions = $user->getAzPermissions();

        // Суперадмин с wildcard '*' — пропускаем всё
        if ($permissions->contains('*')) {
            return true;
        }

        // Проверяем wildcard-паттерны, если функция включена в конфиге
        if (config('az-guard.features.wildcard_permission', false)) {
            foreach ($permissions as $permission) {
                if ($this->matchesWildcard($permission, $ability)) {
                    return true;
                }
            }
        }

        // Точная проверка конкретного права
        if ($permissions->contains($ability)) {
            return true;
        }

        // Возвращаем null (не false!), чтобы Gate продолжил проверку политик
        return null;
    }

    /**
     * Проверяет совпадение по wildcard-паттерну.
     * Пример: 'admin.*' совпадает с 'admin.users.view'.
     */
    protected function matchesWildcard(string $pattern, string $ability): bool
    {
        if (! str_contains($pattern, '*')) {
            return false;
        }

        $regex = '/^' . str_replace(['.', '*'], ['\.', '.*'], $pattern) . '$/';

        return (bool) preg_match($regex, $ability);
    }
}
