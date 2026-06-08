<?php

declare(strict_types=1);

namespace AzGuard\Commands;

use AzGuard\Grants\GrantBuilder;
use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Artisan-команда для отзыва direct grant.
 *
 * @example
 *   # отозвать конкретный grant
 *   php artisan az-guard:revoke-grant 42 app.documents.export app
 *
 *   # отозвать все grants панели для пользователя
 *   php artisan az-guard:revoke-grant 42 -- app --all
 */
final class RevokeGrantCommand extends Command
{
    protected $signature = 'az-guard:revoke-grant
        {user-id        : ID пользователя}
        {permission     : Ключ разрешения (игнорируется при --all)}
        {panel          : Идентификатор панели}
        {--all          : Отозвать все grants данной панели}
        {--model=       : FQCN User-модели (default: auth.providers.users.model)}
        {--force        : Не запрашивать подтверждения для --all}';

    protected $description = 'Отозвать direct grant(s) пользователя AzGuard';

    public function handle(): int
    {
        $userId    = $this->argument('user-id');
        $permKey   = $this->argument('permission');
        $panelId   = $this->argument('panel');
        $all       = (bool) $this->option('all');
        $modelClass = $this->resolveModelClass();

        /** @var Authenticatable|null $user */
        $user = $modelClass::find($userId);

        if ($user === null) {
            $this->error("Пользователь [{$modelClass}] c ID={$userId} не найден.");

            return self::FAILURE;
        }

        $builder = (new GrantBuilder($user))->on($panelId);

        if ($all) {
            if (! $this->option('force') && ! $this->confirm(
                "Отозвать все grants панели [{$panelId}] у пользователя #{$userId}?",
            )) {
                $this->line('\u041eтменено.');

                return self::SUCCESS;
            }

            $deleted = $builder->revokeAll();
            $this->info("✅ Удалено {$deleted} grant(s) для панели [{$panelId}].");

            return self::SUCCESS;
        }

        $deleted = $builder->revoke($permKey);

        if ($deleted === 0) {
            $this->warn("⚠️  Grant [{$permKey}] не найден или уже удалён.");

            return self::SUCCESS;
        }

        $this->info("✅ Grant [{$permKey}] отозван.");

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
