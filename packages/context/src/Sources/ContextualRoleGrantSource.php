<?php

declare(strict_types=1);

namespace AzGuard\Context\Sources;

use AzGuard\Context\AuthorizationContextManager;
use AzGuard\Context\Contracts\MergeStrategy;
use AzGuard\Registry\Contracts\GrantPriority;
use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;
use Override;

/**
 * GrantSource: контекстные права пользователя.
 *
 * Приоритет 95 — выше DatabaseRoleGrantSource (90), ниже ClassRoleGrantSource (100).
 *
 * Логика:
 *  1. Запрашивает глобальный PermissionSet (из других источников) — передаётся через
 *     resolve-цепочку EffectivePermissionResolver.
 *  2. Загружает контекстный PermissionSet из AuthorizationContextManager.
 *  3. Применяет MergeStrategy.
 *
 * Важно: этот источник НЕ дублирует глобальные права — он дополняет или
 * переопределяет их согласно стратегии. Финальный merge происходит в стратегии.
 */
final readonly class ContextualRoleGrantSource implements GrantSource
{
    public function __construct(
        private AuthorizationContextManager $manager,
        private MergeStrategy $strategy,
    ) {}

    #[Override]
    public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet
    {
        // Контекстные права для текущего контекста.
        $contextSet = $this->manager->permissionsFor($user, $panelId);

        // Если контекст не установлен — возвращаем empty (стратегия применится в merge).
        // Стратегии, требующие контекст (DenyWithoutContext), бросят exception здесь.
        return $this->strategy->merge(PermissionSet::empty(), $contextSet);
    }

    #[Override]
    public function priority(): GrantPriority
    {
        return GrantPriority::ContextualRole;
    }
}
