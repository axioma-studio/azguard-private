<?php

declare(strict_types=1);

namespace AzGuard\Context\Strategies;

use AzGuard\Context\Contracts\ContextMergeStrategy;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;
use Override;

/**
 * Стратегия: только контекстные права.
 *
 * Глобальные права полностью игнорируются.
 * Если контекст не установлен — пустой PermissionSet.
 *
 * Подходит для строгой изоляции:
 * пользователь вне контекста не имеет прав,
 * даже если у него есть глобальная роль.
 */
final class ContextOnlyStrategy implements ContextMergeStrategy
{
    #[Override]
    public function merge(
        Authenticatable $user,
        string $panelId,
        PermissionSet $global,
        ?PermissionSet $context,
    ): PermissionSet {
        return $context ?? PermissionSet::empty();
    }
}
