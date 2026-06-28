<?php

declare(strict_types=1);

namespace AzGuard\Contracts;

use AzGuard\Grants\GrantBuilder;
use AzGuard\Models\DirectGrant;
use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Support\Panel;
use BackedEnum;
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
     * Регистрирует панель. Принимает Panel напрямую или callable, возвращающий Panel.
     */
    public function registerPanel(Panel|callable $panel): void;

    /**
     * Возвращает все зарегистрированные панели.
     *
     * @return array<string, Panel>
     */
    public function getPanels(): array;

    /**
     * Возвращает панель по идентификатору (строка или backed enum) или null.
     */
    public function panel(string|BackedEnum $id): ?Panel;

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
    public function permission(string|BackedEnum $panelId, string|UnitEnum $permission): string;

    /**
     * Soft-resolve: возвращает null если панель не зарегистрирована.
     * Безопасен в Blade / UI без try-catch.
     */
    public function tryPermission(string|BackedEnum $panelId, string|UnitEnum $permission): ?string;

    // ─── Extensions ─────────────────────────────────────────────────────────────

    /**
     * Регистрирует кастомный GrantSource (биндит при необходимости и тегирует),
     * чтобы EffectivePermissionResolver подхватил его в цепочку разрешения.
     *
     * @param  class-string<GrantSource>  $sourceClass
     */
    public function registerGrantSource(string $sourceClass): void;

    // ─── Grants API ───────────────────────────────────────────────────────────

    /**
     * Возвращает fluent GrantBuilder для пользователя.
     *
     * AzGuard::forUser($user)->on('app')->ttl(3600)->grant('app.x');
     */
    public function forUser(Authenticatable $user): GrantBuilder;

    /**
     * Короткий хелпер: выдать direct grant.
     *
     * @param  int|null  $ttl  TTL в секундах. null = бессрочно.
     */
    public function grant(
        Authenticatable $user,
        string|UnitEnum $permissionKey,
        string|BackedEnum $panelId = 'app',
        ?int $ttl = null,
    ): DirectGrant;

    /**
     * Короткий хелпер: отозвать direct grant.
     *
     * @return int Количество удалённых записей.
     */
    public function revoke(
        Authenticatable $user,
        string|UnitEnum $permissionKey,
        string|BackedEnum $panelId = 'app',
    ): int;

    /**
     * Короткий хелпер: список активных grants пользователя в панели.
     *
     * @return Collection<int, DirectGrant>
     */
    public function grants(
        Authenticatable $user,
        string|BackedEnum $panelId = 'app',
    ): Collection;
}
