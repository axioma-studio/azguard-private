<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

use AzGuard\Models\DirectGrant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Трейт для Eloquent-моделей (User, Admin, …).
 *
 * Добавляет:
 *  - directGrants()  — отношение
 *  - hasDirectGrant() — проверка наличия активного grant
 *  - Расширение hasAzPermission(): теперь учитывает гранты
 *
 * Использование:
 *   class User extends Authenticatable
 *   {
 *       use HasAzGuard, HasDirectGrants;
 *   }
 */
trait HasDirectGrants
{
    // ─── Relation ─────────────────────────────────────────────────────────────

    /**
     * @return MorphMany<DirectGrant>
     */
    public function directGrants(): MorphMany
    {
        return $this->morphMany(DirectGrant::class, 'grantable');
    }

    // ─── Queries ──────────────────────────────────────────────────────────────

    /**
     * Проверяет наличие активного direct grant для конкретного права.
     *
     * @param  string       $permissionKey  Ключ права, например 'app.documents.export'
     * @param  string|null  $panelId        Явная панель; если null — берётся текущая из AzGuard
     */
    public function hasDirectGrant(string $permissionKey, ?string $panelId = null): bool
    {
        $panel = $panelId ?? \AzGuard\Facades\AzGuard::currentPanel()?->getId();

        if ($panel === null) {
            return false;
        }

        return $this->directGrants()
            ->where('panel_id', $panel)
            ->where('permission_key', $permissionKey)
            ->active()
            ->exists();
    }

    /**
     * Возвращает все активные grants пользователя для панели.
     *
     * @return Collection<int, DirectGrant>
     */
    public function activeDirectGrants(?string $panelId = null): Collection
    {
        $panel = $panelId ?? \AzGuard\Facades\AzGuard::currentPanel()?->getId();

        $query = $this->directGrants()->active();

        if ($panel !== null) {
            $query->forPanel($panel);
        }

        return $query->get();
    }

    // ─── Override hasAzPermission ─────────────────────────────────────────────

    /**
     * Переопределяет HasAzGuard::hasAzPermission():
     * возвращает true если пользователь имеет право через роль ИЛИ через direct grant.
     *
     * HasAzGuard должен быть подключён в той же модели.
     */
    public function hasAzPermission(string $permission, ?string $panelId = null): bool
    {
        // Родительский метод из HasAzGuard (через роли)
        if (method_exists(parent::class, 'hasAzPermission')) {
            /** @phpstan-ignore-next-line */
            if (parent::hasAzPermission($permission, $panelId)) {
                return true;
            }
        }

        return $this->hasDirectGrant($permission, $panelId);
    }
}
