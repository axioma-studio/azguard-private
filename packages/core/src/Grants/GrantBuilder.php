<?php

declare(strict_types=1);

namespace AzGuard\Grants;

use AzGuard\Models\DirectGrant;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Fluent builder для выдачи / отзыва direct grants.
 *
 * Использование:
 *
 *   AzGuard::forUser($user)
 *       ->on('app')
 *       ->give('app.documents.export')
 *       ->ttl(3600)
 *       ->save();
 *
 *   AzGuard::forUser($user)
 *       ->on('app')
 *       ->revoke('app.documents.export');
 *
 *   AzGuard::forUser($user)->on('app')->revokeAll();
 */
final class GrantBuilder
{
    private string  $panelId    = 'app';
    private ?int    $ttlSeconds = null;

    public function __construct(
        private readonly Authenticatable $user,
    ) {}

    /**
     * Установить панель.
     */
    public function on(string $panelId): static
    {
        $clone = clone $this;
        $clone->panelId = $panelId;

        return $clone;
    }

    /**
     * Установить TTL в секундах (назначается перед ->give()).
     */
    public function ttl(int $seconds): static
    {
        $clone = clone $this;
        $clone->ttlSeconds = $seconds;

        return $clone;
    }

    /**
     * Сохранить grant: создаёт или обновляет запись в БД и диспатчит GrantGiven.
     * Возвращает сохранённую модель DirectGrant.
     */
    public function give(string $permissionKey): DirectGrant
    {
        $pending = new PendingGrant(
            user:          $this->user,
            panelId:       $this->panelId,
            permissionKey: $permissionKey,
            ttlSeconds:    $this->ttlSeconds,
        );

        $modelClass = get_class($this->user);
        $userId     = $this->user->getAuthIdentifier();
        $expiresAt  = $pending->expiresAt();

        $grant = DirectGrant::firstOrNew([
            'model_type'     => $modelClass,
            'model_id'       => $userId,
            'permission_key' => $permissionKey,
            'panel_id'       => $this->panelId,
        ]);

        $grant->expires_at = $expiresAt;
        $grant->save();

        event(new \AzGuard\Events\GrantGiven(
            user:          $this->user,
            permissionKey: $permissionKey,
            panelId:       $this->panelId,
            expiresAt:     $expiresAt,
        ));

        return $grant;
    }

    /**
     * Отозвать конкретный grant. Возвращает количество удалённых записей.
     */
    public function revoke(string $permissionKey): int
    {
        $deleted = DirectGrant::where('model_type', get_class($this->user))
            ->where('model_id', $this->user->getAuthIdentifier())
            ->where('permission_key', $permissionKey)
            ->where('panel_id', $this->panelId)
            ->delete();

        if ($deleted > 0) {
            event(new \AzGuard\Events\GrantRevoked(
                user:          $this->user,
                permissionKey: $permissionKey,
                panelId:       $this->panelId,
            ));
        }

        return $deleted;
    }

    /**
     * Отозвать все direct grants пользователя в данной панели.
     */
    public function revokeAll(): int
    {
        $keys = DirectGrant::where('model_type', get_class($this->user))
            ->where('model_id', $this->user->getAuthIdentifier())
            ->where('panel_id', $this->panelId)
            ->pluck('permission_key')
            ->all();

        if (empty($keys)) {
            return 0;
        }

        $deleted = DirectGrant::where('model_type', get_class($this->user))
            ->where('model_id', $this->user->getAuthIdentifier())
            ->where('panel_id', $this->panelId)
            ->delete();

        foreach ($keys as $key) {
            event(new \AzGuard\Events\GrantRevoked(
                user:          $this->user,
                permissionKey: $key,
                panelId:       $this->panelId,
            ));
        }

        return $deleted;
    }

    /**
     * Вернуть все активные grants пользователя для данной панели.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, DirectGrant>
     */
    public function list(): \Illuminate\Database\Eloquent\Collection
    {
        return DirectGrant::where('model_type', get_class($this->user))
            ->where('model_id', $this->user->getAuthIdentifier())
            ->where('panel_id', $this->panelId)
            ->active()
            ->get();
    }
}
