<?php

declare(strict_types=1);

namespace AzGuard\Context\Strategies;

use AzGuard\Context\Contracts\MergeStrategy;
use AzGuard\Registry\Values\PermissionSet;

/**
 * Стратегия: глобальные права ∪ контекстные права.
 *
 * Пользователь получает всё, что есть в его глобальных ролях,
 * плюс дополнительные права из контекста (workspace / site).
 *
 * Это стратегия по умолчанию — наименее ограничивающая.
 *
 * Пример: глобальная роль даёт app.posts.view,
 * контекст workspace:42 добавляет app.posts.publish.
 * Итог: {app.posts.view, app.posts.publish}.
 */
final class GlobalPlusContextStrategy implements MergeStrategy
{
    public function merge(PermissionSet $global, ?PermissionSet $context): PermissionSet
    {
        if ($context === null) {
            return $global;
        }

        return $global->merge($context);
    }
}
