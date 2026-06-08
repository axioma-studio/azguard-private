<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

use AzGuard\Models\DirectGrant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait для User-модели. Предоставляет:
 * - связь directGrants()
 * - проверку hasDirectGrant(key, panel?)
 * - расширение hasAzPermission(): роль ИЛИ direct grant
 * - сброс кэша direct grants
 *
 * Подключайте в User-модели вместе с HasAzGuard:
 *
 *   use HasAzGuard, HasDirectGrants;
 *
 * Порядок проверки hasAzPermission():
 *   1. Если хоть одна роль даёт разрешение (через HasAzGuard) → true
 *   2. Иначе проверяет direct grants для текущей панели
 */
trait HasDirectGrants
{
    /** @var array<string, bool> key => bool, in-memory кэш */
    private array $directGrantCache = [];

    // -------------------------------------------------------------------------
    // Eloquent relation
    // -------------------------------------------------------------------------

    /**
     * Все direct grants пользователя (по всем панелям).
     * Используйте directGrants('app') для фильтрации по панели.
     *
     * @return MorphMany<DirectGrant>
     */
    public function directGrantsRelation(): MorphMany
    {
        return $this->morphMany(
            related: config('az-guard.models.direct_grant', DirectGrant::class),
            name: 'model',
        );
    }

    /**
     * Получить активные direct grants, опционально отфильтрованные по панели.
     *
     * @return Collection<int, DirectGrant>
     */
    public function directGrants(?string $panelId = null): Collection
    {
        $query = $this->directGrantsRelation()->active();

        if ($panelId !== null) {
            $query->where('panel_id', $panelId);
        }

        return $query->get();
    }

    // -------------------------------------------------------------------------
    // Checks
    // -------------------------------------------------------------------------

    /**
     * Проверить, есть ли активный direct grant для данного ключа.
     *
     * @param  string       $permissionKey  Полное имя разрешения
     * @param  string|null  $panelId        Если null, используется текущая панель AzGuard
     */
    public function hasDirectGrant(string $permissionKey, ?string $panelId = null): bool
    {
        $resolvedPanel = $panelId ?? \AzGuard\Facades\AzGuard::currentPanel()?->getId();

        $cacheKey = $resolvedPanel . '::' . $permissionKey;

        if (array_key_exists($cacheKey, $this->directGrantCache)) {
            return $this->directGrantCache[$cacheKey];
        }

        $this->directGrantCache[$cacheKey] = DirectGrant::where('model_type', get_class($this))
            ->where('model_id', $this->getKey())
            ->where('permission_key', $permissionKey)
            ->when($resolvedPanel !== null, fn ($q) => $q->where('panel_id', $resolvedPanel))
            ->active()
            ->exists();

        return $this->directGrantCache[$cacheKey];
    }

    /**
     * Расширяет HasAzGuard::hasAzPermission():
     * роль ИЛИ direct grant → true.
     *
     * Должен использоваться в трейте вместе с HasAzGuard.
     */
    public function hasAzPermission(string $permission): bool
    {
        // 1. Проверяем через роли (HasAzGuard)
        if ($this->hasAzPermissionViaRole($permission)) {
            return true;
        }

        // 2. Проверяем через direct grants
        return $this->hasDirectGrant($permission);
    }

    /**
     * Проверка через роли (логика из HasAzGuard).
     * Метод protected, чтобы HasDirectGrants мог вызывать его, не переопределяя полностью.
     */
    protected function hasAzPermissionViaRole(string $permission): bool
    {
        $permissions = $this->getAzPermissions();

        if ($permissions->contains('*')) {
            return true;
        }

        if (config('az-guard.features.wildcard_permission', false)) {
            foreach ($permissions as $p) {
                if (str_contains($p, '*')) {
                    $regex = '/^' . str_replace(['.', '*'], ['\\.', '.*'], $p) . '$/';
                    if (preg_match($regex, $permission)) {
                        return true;
                    }
                }
            }
        }

        return $permissions->contains($permission);
    }

    // -------------------------------------------------------------------------
    // Cache management
    // -------------------------------------------------------------------------

    /**
     * Сбросить in-memory кэш direct grants.
     */
    public function clearDirectGrantsCache(): void
    {
        $this->directGrantCache = [];
    }

    /**
     * Переопределяет через HasAzGuard: сбрасывает оба кэша сразу.
     */
    public function clearAzPermissionsCache(): void
    {
        $this->clearDirectGrantsCache();

        // вызываем оригинальный метод HasAzGuard через parent
        parent::clearAzPermissionsCache();
    }
}
