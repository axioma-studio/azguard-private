<?php

declare(strict_types=1);

namespace AzGuard\Context\Strategies;

use AzGuard\Context\Contracts\MergeStrategy;
use AzGuard\Registry\Values\PermissionSet;

/**
 * Стратегия: только контекстные права, глобальные игнорируются.
 *
 * Использовать, когда права полностью определяются контекстом (workspace / site),
 * а глобальные роли не должны давать доступ внутри контекста.
 *
 * Пример: SaaS с изолированными workspace-ами, где роль суперадмина
 * не должна автоматически давать права внутри чужого workspace.
 *
 * Если контекст не установлен — возвращает empty (deny all).
 */
final class ContextOnlyStrategy implements MergeStrategy
{
    public function merge(PermissionSet $global, ?PermissionSet $context): PermissionSet
    {
        return $context ?? PermissionSet::empty();
    }
}
