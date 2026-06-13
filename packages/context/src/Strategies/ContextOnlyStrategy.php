<?php

declare(strict_types=1);

namespace AzGuard\Context\Strategies;

use AzGuard\Context\Contracts\MergeStrategy;
use AzGuard\Registry\Values\PermissionSet;
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
final class ContextOnlyStrategy implements MergeStrategy
{
    #[Override]
    public function merge(PermissionSet $global, ?PermissionSet $context): PermissionSet
    {
        return $context ?? PermissionSet::empty();
    }
}
