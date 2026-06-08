<?php

declare(strict_types=1);

namespace AzGuard\Events;

use AzGuard\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: роль назначена пользователю (или другой модели).
 *
 * Используется для:
 * - автоматической очистки кэша прав
 * - audit-лога (если features.audit_log = true)
 * - уведомлений
 */
final class RoleAttached
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Model $model,
        public readonly Role $role,
    ) {}
}
