<?php

declare(strict_types=1);

namespace AzGuard\Context\Strategies;

use AzGuard\Context\Contracts\MergeStrategy;
use AzGuard\Context\Exceptions\MissingAuthorizationContextException;
use AzGuard\Registry\Values\PermissionSet;

/**
 * Стратегия: deny если контекст не установлен; иначе — глобальные ∪ контекстные.
 *
 * Использовать для маршрутов, где контекст обязателен.
 * Если middleware azguard.context не установил контекст — бросает exception.
 *
 * Пример: API workspace-ресурсов, где каждый запрос обязан содержать workspace_id.
 */
final class DenyWithoutContextStrategy implements MergeStrategy
{
    public function merge(PermissionSet $global, ?PermissionSet $context): PermissionSet
    {
        if ($context === null) {
            throw new MissingAuthorizationContextException(
                'Authorization context is required but was not set. '
                . 'Ensure the azguard.context middleware is applied to this route.'
            );
        }

        return $global->merge($context);
    }
}
