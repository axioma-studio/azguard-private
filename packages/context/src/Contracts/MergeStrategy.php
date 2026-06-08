<?php

declare(strict_types=1);

namespace AzGuard\Context\Contracts;

use AzGuard\Registry\Values\PermissionSet;

/**
 * Стратегия объединения глобальных и контекстных прав.
 *
 * @see GlobalPlusContextStrategy  глобальные ∪ контекстные
 * @see ContextOnlyStrategy        только контекстные
 * @see DenyWithoutContextStrategy deny если контекст отсутствует
 */
interface MergeStrategy
{
    /**
     * @param PermissionSet      $global   права из глобальных источников (ClassRole, DatabaseRole)
     * @param PermissionSet|null $context  права из контекста (null = контекст не установлен)
     */
    public function merge(PermissionSet $global, ?PermissionSet $context): PermissionSet;
}
