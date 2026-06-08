<?php

declare(strict_types=1);

namespace AzGuard\Commands;

use AzGuard\Models\ModelHasScope;
use Illuminate\Console\Command;

/**
 * Artisan command: azguard:list-scoped-roles {user}
 *
 * Lists all entity-scoped role assignments for a given user.
 * User can be identified by ID or email.
 *
 * Usage:
 *   php artisan azguard:list-scoped-roles 1
 *   php artisan azguard:list-scoped-roles admin@example.com
 *   php artisan azguard:list-scoped-roles 1 --entity="App\Models\Project"
 */
class ListScopedRolesCommand extends Command
{
    protected $signature = 'azguard:list-scoped-roles
                            {user : ID или email пользователя}
                            {--entity= : Фильтр по типу сущности (FQCN, например App\\Models\\Project)}';

    protected $description = 'Вывести все entity-scoped роли пользователя';

    public function handle(): int
    {
        /** @var class-string $userModelClass */
        $userModelClass = config('auth.providers.users.model', \App\Models\User::class);

        $identifier = $this->argument('user');

        $user = is_numeric($identifier)
            ? $userModelClass::find((int) $identifier)
            : $userModelClass::where('email', $identifier)->first();

        if ($user === null) {
            $this->error("Пользователь [{$identifier}] не найден.");

            return self::FAILURE;
        }

        $query = ModelHasScope::query()
            ->where('model_type', $user->getMorphClass())
            ->where('model_id', $user->getKey())
            ->whereNotNull('role_id')
            ->with('role');

        if ($entityFilter = $this->option('entity')) {
            $query->where('scope_entity_type', $entityFilter);
        }

        $scopes = $query->get();

        if ($scopes->isEmpty()) {
            $this->warn("У пользователя [{$identifier}] нет scoped-ролей.");

            return self::SUCCESS;
        }

        $this->info("Scoped-роли пользователя: <comment>{$identifier}</comment>");
        $this->line('');

        $rows = $scopes->map(fn ($scope): array => [
            $scope->role?->name ?? '—',
            $scope->scope_entity_type ?? '—',
            (string) ($scope->scope_entity_id ?? '—'),
            $scope->scope_class,
        ])->toArray();

        $this->table(
            ['Роль', 'Тип сущности', 'ID сущности', 'Scope Class'],
            $rows,
        );

        return self::SUCCESS;
    }
}
