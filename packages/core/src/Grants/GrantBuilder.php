<?php

declare(strict_types=1);

namespace AzGuard\Grants;

use AzGuard\Events\GrantGiven;
use AzGuard\Events\GrantRevoked;
use AzGuard\Models\DirectGrant;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Fluent-строитель для работы с Direct Grants.
 *
 * Использование:
 *   AzGuard::forUser($user)->on('app')->ttl(3600)->give('app.documents.export');
 *   AzGuard::forUser($user)->on('app')->revoke('app.documents.export');
 *   AzGuard::forUser($user)->on('app')->list();
 */
final class GrantBuilder
{
    private ?string $panelId = null;
    private ?int $ttlSeconds = null;

    public function __construct(
        private readonly Authenticatable $user,
    ) {}

    // ─── Fluent setters ───────────────────────────────────────────────────────

    public function on(string $panelId): static
    {
        $this->panelId = $panelId;

        return $this;
    }

    /**
     * Установить TTL в секундах. null = бессрочно.
     */
    public function ttl(?int $seconds): static
    {
        $this->ttlSeconds = $seconds;

        return $this;
    }

    // ─── Actions ──────────────────────────────────────────────────────────────

    /**
     * Выдать право (или обновить expires_at существующего).
     *
     * Idempotent: повторный вызов обновляет expires_at.
     */
    public function give(string $permissionKey): DirectGrant
    {
        $panel = $this->resolvePanel();

        $expiresAt = $this->ttlSeconds !== null
            ? Carbon::now()->addSeconds($this->ttlSeconds)
            : null;

        /** @var DirectGrant $grant */
        $grant = DirectGrant::query()->updateOrCreate(
            [
                'grantable_type'  => $this->user::class,
                'grantable_id'    => $this->user->getAuthIdentifier(),
                'panel_id'        => $panel,
                'permission_key'  => $permissionKey,
            ],
            [
                'expires_at' => $expiresAt,
            ],
        );

        event(new GrantGiven(
            user: $this->user,
            permissionKey: $permissionKey,
            panelId: $panel,
            grant: $grant,
        ));

        return $grant;
    }

    /**
     * Отозвать конкретное право.
     *
     * @return int  Количество удалённых записей (0 или 1).
     */
    public function revoke(string $permissionKey): int
    {
        $panel = $this->resolvePanel();

        $deleted = DirectGrant::query()
            ->where('grantable_type', $this->user::class)
            ->where('grantable_id', $this->user->getAuthIdentifier())
            ->where('panel_id', $panel)
            ->where('permission_key', $permissionKey)
            ->delete();

        if ($deleted > 0) {
            event(new GrantRevoked(
                user: $this->user,
                permissionKey: $permissionKey,
                panelId: $panel,
            ));
        }

        return (int) $deleted;
    }

    /**
     * Отозвать все права пользователя в панели.
     *
     * @return int  Количество удалённых записей.
     */
    public function revokeAll(): int
    {
        $panel = $this->resolvePanel();

        $deleted = DirectGrant::query()
            ->where('grantable_type', $this->user::class)
            ->where('grantable_id', $this->user->getAuthIdentifier())
            ->where('panel_id', $panel)
            ->delete();

        if ($deleted > 0) {
            event(new GrantRevoked(
                user: $this->user,
                permissionKey: '*',
                panelId: $panel,
            ));
        }

        return (int) $deleted;
    }

    /**
     * Вернуть все активные grants пользователя в панели.
     *
     * @return Collection<int, DirectGrant>
     */
    public function list(): Collection
    {
        $panel = $this->resolvePanel();

        return DirectGrant::query()
            ->where('grantable_type', $this->user::class)
            ->where('grantable_id', $this->user->getAuthIdentifier())
            ->where('panel_id', $panel)
            ->active()
            ->get();
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    private function resolvePanel(): string
    {
        if ($this->panelId !== null) {
            return $this->panelId;
        }

        $current = \AzGuard\Facades\AzGuard::currentPanel();

        if ($current === null) {
            throw new \RuntimeException(
                'AzGuard\Grants\GrantBuilder: панель не указана. Вызовите ->on("panel-id") или установите текущую панель.',
            );
        }

        return $current->getId();
    }
}
