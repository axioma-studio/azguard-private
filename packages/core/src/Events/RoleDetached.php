<?php

declare(strict_types=1);

namespace AzGuard\Events;

use AzGuard\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: роль отозвана у пользователя (или другой модели).
 *
 * Используется для:
 * - автоматической очистки кэша прав
 * - audit-лога (если features.audit_log = true)
 * - уведомлений
 */
final readonly class RoleDetached
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $model,
        public Role $role,
    ) {}
}
