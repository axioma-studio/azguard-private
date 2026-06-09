<?php

declare(strict_types=1);

namespace AzGuard\Context\Contracts;

use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Стратегия объединения глобальных прав и контекстных прав.
 *
 * Три встроенных стратегии:
 *  - GlobalPlusContextStrategy  — global ∪ context (дефолт)
 *  - ContextOnlyStrategy        — только context, global игнорируется
 *  - DenyWithoutContextStrategy — пустой set если контекст не установлен
 *
 * Подключается в конфиге:
 *   'context' => ['merge_strategy' => GlobalPlusContextStrategy::class]
 */
interface ContextMergeStrategy
{
    /**
     * @param  PermissionSet  $global  Права из глобальных источников (ClassRole, DatabaseRole, Direct)
     * @param  PermissionSet|null  $context  Права из контекстного источника (null = контекст не установлен)
     */
    public function merge(
        Authenticatable $user,
        string $panelId,
        PermissionSet $global,
        ?PermissionSet $context,
    ): PermissionSet;
}
