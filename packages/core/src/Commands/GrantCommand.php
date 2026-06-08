<?php

declare(strict_types=1);

namespace AzGuard\Commands;

use AzGuard\Models\DirectGrant;
use AzGuard\Grants\GrantBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Artisan-команда для выдачи direct grant пользователю.
 *
 * @example
 *   php artisan az-guard:grant 42 app.documents.export app --ttl=3600
 *   php artisan az-guard:grant 42 app.documents.export app --model=App\\Models\\Admin
 */
final class GrantCommand extends Command
{
    protected $signature = 'az-guard:grant
        {user-id        : ID пользователя}
        {permission     : Ключ разрешения (app.documents.export)}
        {panel          : Идентификатор панели}
        {--ttl=         : TTL в секундах (если не задан — бессрочно)}
        {--model=       : FQCN User-модели (default: auth.providers.users.model)}';

    protected $description = 'Выдать direct grant пользователю AzGuard';

    public function handle(): int
    {
        $userId    = $this->argument('user-id');
        $permKey   = $this->argument('permission');
        $panelId   = $this->argument('panel');
        $ttl       = $this->option('ttl') !== null ? (int) $this->option('ttl') : null;
        $modelClass = $this->resolveModelClass();

        /** @var Authenticatable|null $user */
        $user = $modelClass::find($userId);

        if ($user === null) {
            $this->error("Пользователь [{$modelClass}] c ID={$userId} не найден.");

            return self::FAILURE;
        }

        $builder = (new GrantBuilder($user))->on($panelId);

        if ($ttl !== null) {
            $builder = $builder->ttl($ttl);
        }

        $grant = $builder->give($permKey);

        $expiresAt = $grant->expires_at instanceof CarbonImmutable
            ? $grant->expires_at->toDateTimeString()
            : ($grant->expires_at ? (string) $grant->expires_at : 'бессрочно');

        $this->table(
            ['User ID', 'Model', 'Permission', 'Panel', 'Expires at'],
            [[
                $user->getAuthIdentifier(),
                $modelClass,
                $permKey,
                $panelId,
                $expiresAt,
            ]],
        );

        $this->info("✅ Grant выдан.");

        return self::SUCCESS;
    }

    private function resolveModelClass(): string
    {
        /** @var string|null $option */
        $option = $this->option('model');

        if ($option !== null && $option !== '') {
            return $option;
        }

        return (string) config('auth.providers.users.model', 'App\\Models\\User');
    }
}
