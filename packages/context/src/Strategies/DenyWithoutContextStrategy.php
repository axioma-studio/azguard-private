<?php

declare(strict_types=1);

namespace AzGuard\Context\Strategies;

use AzGuard\Context\Contracts\ContextMergeStrategy;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;
use Override;

/**
 * Стратегия: запретить всё без контекста, с контекстом — global ∪ context.
 *
 * Если контекст не установлен — возвращает пустой PermissionSet,
 * блокируя любой доступ.
 * Если контекст установлен — поведение как GlobalPlusContextStrategy.
 *
 * Подходит для панелей, где работа вне контекста
 * (например, вне выбранного проекта) семантически невозможна.
 */
final class DenyWithoutContextStrategy implements ContextMergeStrategy
{
    #[Override]
    public function merge(
        Authenticatable $user,
        string $panelId,
        PermissionSet $global,
        ?PermissionSet $context,
    ): PermissionSet {
        if (! $context instanceof PermissionSet) {
            return PermissionSet::empty();
        }

        return $global->merge($context);
    }
}
