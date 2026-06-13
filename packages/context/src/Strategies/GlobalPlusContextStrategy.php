<?php

declare(strict_types=1);

namespace AzGuard\Context\Strategies;

use AzGuard\Context\Contracts\MergeStrategy;
use AzGuard\Registry\Values\PermissionSet;
use Override;

/**
 * Стратегия: global ∪ context.
 *
 * Пользователь получает объединение глобальных прав и прав контекста.
 * Если контекст не установлен — только глобальные права.
 *
 * Подходит для большинства multi-workspace сценариев:
 * администратор платформы имеет глобальные права,
 * участник workspace — права своего workspace.
 */
final class GlobalPlusContextStrategy implements MergeStrategy
{
    #[Override]
    public function merge(PermissionSet $global, ?PermissionSet $context): PermissionSet
    {
        if (! $context instanceof PermissionSet) {
            return $global;
        }

        return $global->merge($context);
    }
}
