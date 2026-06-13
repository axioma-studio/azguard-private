<?php

declare(strict_types=1);

namespace AzGuard\Context;

use AzGuard\Context\Contracts\MergeStrategy;
use AzGuard\Registry\Contracts\GrantPriority;
use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Registry\Values\PermissionSet;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Override;

/**
 * GrantSource: права из контекстных ролей.
 *
 * Приоритет 95 (между ClassRole=100 и DatabaseRole=90).
 *
 * Алгоритм:
 *  1. Получить активный AuthorizationContext для panelId.
 *  2. Если контекст не установлен — делегировать стратегии (empty / global).
 *  3. Загрузить права из az_guard_context_roles (contextType + contextId + userId).
 *  4. Передать глобальный + контекстный PermissionSet в стратегию.
 *
 * Таблица az_guard_context_roles:
 *   id, model_type, model_id, context_type, context_id, panel_id,
 *   permission_key, created_at, updated_at
 */
final readonly class ContextualRoleGrantSource implements GrantSource
{
    public function __construct(
        private AuthorizationContextManager $manager,
        private MergeStrategy $strategy,
    ) {}

    #[Override]
    public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet
    {
        $context = $this->manager->current($panelId);

        // Контекст не установлен — стратегия решает (пустой или что-то ещё)
        if (! $context instanceof AuthorizationContext) {
            return $this->strategy->merge(PermissionSet::empty(), null);
        }

        $userId = $user->getAuthIdentifier();
        $userClass = $user::class;

        $table = config('az-guard.table_names.context_roles', 'az_guard_context_roles');

        $keys = DB::table($table)
            ->where('model_type', $userClass)
            ->where('model_id', $userId)
            ->where('context_type', $context->contextType)
            ->where('context_id', $context->contextId)
            ->where('panel_id', $panelId)
            ->pluck('permission_key')
            ->all();

        // global-часть = пустой set: этот source отвечает только за context-слой.
        // Merge с глобальными правами происходит в EffectivePermissionResolver
        // через обычные источники (ClassRole, DatabaseRole).
        return $keys === []
            ? PermissionSet::empty()
            : (in_array('*', $keys, strict: true)
                ? PermissionSet::wildcard()
                : PermissionSet::fromKeys($keys));
    }

    #[Override]
    public function priority(): GrantPriority
    {
        return GrantPriority::ContextualRole;
    }
}
