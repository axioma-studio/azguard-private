<?php

declare(strict_types=1);

namespace AzGuard\Contracts;

use AzGuard\Grants\GrantBuilder;
use AzGuard\Support\Panel;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Контракт для AzGuardManager.
 * Используйте этот интерфейс в type-hint'ах для тестируемости через mock.
 */
interface AzGuardManagerInterface
{
    /**
     * Регистрирует панель через замыкание.
     */
    public function registerPanel(Closure $panel): void;

    /**
     * Возвращает все зарегистрированные панели.
     *
     * @return array<string, Panel>
     */
    public function getPanels(): array;

    /**
     * Возвращает панель по идентификатору или null.
     */
    public function panel(string $id): ?Panel;

    /**
     * Возвращает текущую активную панель.
     */
    public function currentPanel(): ?Panel;

    /**
     * Устанавливает текущую активную панель.
     */
    public function setCurrentPanel(?Panel $panel): void;

    /**
     * Разрешает полное имя разрешения для панели.
     *
     * @throws \RuntimeException если панель не зарегистрирована
     */
    public function permission(string $panelId, string|\UnitEnum $permission): string;

    // -------------------------------------------------------------------------
    // Fluent Grants API (Phase 5)
    // -------------------------------------------------------------------------

    /**
     * Получить fluent builder для управления grants пользователя.
     *
     * AzGuard::forUser($user)->on('app')->give('app.x.view');
     */
    public function forUser(Authenticatable $user): GrantBuilder;

    /**
     * Выдать direct grant (короткий способ).
     *
     * AzGuard::grantDirect($user, 'app.x.view', 'app', ttl: 3600);
     */
    public function grantDirect(
        Authenticatable $user,
        string          $permissionKey,
        string          $panelId = 'app',
        ?int            $ttl = null,
    ): \AzGuard\Models\DirectGrant;

    /**
     * Отозвать direct grant (короткий способ). Возвращает количество удалённых записей.
     */
    public function revokeDirect(
        Authenticatable $user,
        string          $permissionKey,
        string          $panelId = 'app',
    ): int;

    /**
     * Вернуть все активные direct grants пользователя для панели.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \AzGuard\Models\DirectGrant>
     */
    public function activeGrants(
        Authenticatable $user,
        string          $panelId = 'app',
    ): \Illuminate\Database\Eloquent\Collection;
}
