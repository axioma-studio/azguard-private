<?php

declare(strict_types=1);

namespace AzGuard\Guard;

use AzGuard\Registry\Resolver\EffectivePermissionResolver;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Основной компонент авторизации AzGuard.
 *
 * Регистрируется через Gate::before() и:
 * 1) Делегирует разрешение прав в EffectivePermissionResolver.
 * 2) Возвращает true для суперадмина (wildcard '*').
 * 3) Проверяет конкретное право $ability через PermissionSet.
 * 4) Возвращает null (pass-through) если пользователь не использует HasAzGuard.
 *
 * Панель определяется из текущего запроса (SetCurrentPanel middleware);
 * если панель не установлена — берётся первая зарегистрированная.
 */
final class Authorizer
{
    public function __construct(
        private readonly EffectivePermissionResolver $resolver,
    ) {}

    public function check(Authorizable $user, string $ability): ?bool
    {
        if (! $user instanceof Authenticatable) {
            return null;
        }

        $panelId = $this->resolvePanelId();

        if ($panelId === null) {
            return null;
        }

        $set = $this->resolver->forUser($user, $panelId);

        // Суперадмин с wildcard '*' — пропускаем всё
        if ($set->isWildcard()) {
            return true;
        }

        // Проверяем wildcard-паттерны, если функция включена в конфиге
        if (config('az-guard.features.wildcard_permission', false)) {
            foreach ($set->keys() as $permission) {
                if ($this->matchesWildcard($permission, $ability)) {
                    return true;
                }
            }
        }

        if ($set->has($ability)) {
            return true;
        }

        // Возвращаем null (не false!), чтобы Gate продолжил проверку политик
        return null;
    }

    private function resolvePanelId(): ?string
    {
        // Приоритет: панель текущего запроса (SetCurrentPanel middleware),
        // затем первая зарегистрированная панель.
        $manager = app(\AzGuard\AzGuardManager::class);

        $current = $manager->currentPanel();

        if ($current !== null) {
            return $current->getId();
        }

        $panels = $manager->getPanels();

        if ($panels === []) {
            return null;
        }

        return array_key_first($panels);
    }

    /**
     * Проверяет совпадение по wildcard-паттерну.
     * Пример: 'admin.*' совпадает с 'admin.users.view'.
     */
    private function matchesWildcard(string $pattern, string $ability): bool
    {
        if (! str_contains($pattern, '*')) {
            return false;
        }

        $regex = '/^' . str_replace(['.', '*'], ['\.', '.*'], $pattern) . '$/';

        return (bool) preg_match($regex, $ability);
    }
}
