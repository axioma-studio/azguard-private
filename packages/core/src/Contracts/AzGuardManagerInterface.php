<?php

declare(strict_types=1);

namespace AzGuard\Contracts;

use AzGuard\Grants\GrantBuilder;
use AzGuard\Models\DirectGrant;
use AzGuard\Support\Panel;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;
use UnitEnum;

/**
 * Контракт для AzGuardManager.
 * Используйте этот интерфейс в type-hint'ах для тестируемости через mock.
 */
interface AzGuardManagerInterface
{
    // ─── Panels ───────────────────────────────────────────────────────────────

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
     * @throws RuntimeException если панель не зарегистрирована
     */
    public function permission(string $panelId, string|UnitEnum $permission): string;

    /**
     * Soft-resolve: возвращает null если панель не зарегистрирована.
     * Безопасен в Blade / UI без try-catch.
     */
    public function tryPermission(string $panelId, string|UnitEnum $permission): ?string;

    // ─── Grants API ───────────────────────────────────────────────────────────

    /**
     * Возвращает fluent GrantBuilder для пользователя.
     *
     * AzGuard::forUser($user)->on('app')->ttl(3600)->give('app.x');
     */
    public function forUser(Authenticatable $user): GrantBuilder;

    /**
     * Короткий хелпер: выдать direct grant.
     *
     * @param  int|null  $ttl  TTL в секундах. null = бессрочно.
     */
    public function grantDirect(
        Authenticatable $user,
        string $permissionKey,
        string $panelId,
        ?int $ttl,
    ): DirectGrant;

    /**
     * Короткий хелпер: отозвать direct grant.
     *
     * @return int Количество удалённых записей.
     */
    public function revokeDirect(
        Authenticatable $user,
        string $permissionKey,
        string $panelId,
    ): int;

    /**
     * Короткий хелпер: список активных grants пользователя в панели.
     *
     * @return Collection<int, DirectGrant>
     */
    public function activeGrants(
        Authenticatable $user,
        string $panelId,
    ): Collection;
}
