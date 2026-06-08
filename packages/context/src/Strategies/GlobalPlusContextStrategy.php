<?php

declare(strict_types=1);

namespace AzGuard\Context\Strategies;

use AzGuard\Context\Contracts\ContextMergeStrategy;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;

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
final class GlobalPlusContextStrategy implements ContextMergeStrategy
{
    public function merge(
        Authenticatable $user,
        string $panelId,
        PermissionSet $global,
        ?PermissionSet $context,
    ): PermissionSet {
        if ($context === null) {
            return $global;
        }

        return $global->merge($context);
    }
}
