<?php

declare(strict_types=1);

namespace AzGuard\Commands;

use AzGuard\Commands\Concerns\ResolvesUserModel;
use AzGuard\Models\DirectGrant;
use Illuminate\Console\Command;

/**
 * Revoke a direct grant (or all grants) from a user.
 *
 * Examples:
 *   php artisan guard:revoke 1 app.documents.export --panel=app
 *   php artisan guard:revoke 1 --all --panel=app
 *   php artisan guard:revoke 1 --all
 */
final class RevokeCommand extends Command
{
    use ResolvesUserModel;

    protected $signature = 'guard:revoke
        {user_id         : User ID (or model ID)}
        {permission_key? : Permission key (not required with --all)}
        {--panel=        : Panel ID (omit to target all panels when used with --all)}
        {--all           : Revoke all grants for the user (in the given panel or all panels)}
        {--model=        : User model FQCN (defaults to auth.providers.users.model)}
        {--force         : Skip confirmation prompt}';

    protected $description = 'Revoke a direct permission grant from a user';

    public function handle(): int
    {
        $userId = $this->argument('user_id');
        $permissionKey = $this->argument('permission_key');
        $panelId = $this->option('panel');
        $revokeAll = (bool) $this->option('all');
        $force = (bool) $this->option('force');
        $modelClass = $this->resolveUserModelClass();

        if (! $revokeAll && $permissionKey === null) {
            $this->error('Specify a permission_key or use --all.');

            return self::FAILURE;
        }

        if (! $revokeAll && $panelId === null) {
            $this->error('Specify --panel when revoking a specific permission.');

            return self::FAILURE;
        }

        if (! class_exists($modelClass)) {
            $this->error("Model class [{$modelClass}] not found.");

            return self::FAILURE;
        }

        $query = DirectGrant::where('model_type', $modelClass)
            ->where('model_id', $userId);

        if ($revokeAll) {
            if ($panelId !== null) {
                $query->where('panel_id', $panelId);
            }

            $panelLabel = $panelId ?? 'all panels';
            $label = "all grants (panel: {$panelLabel})";
        } else {
            $query->where('permission_key', $permissionKey)
                ->where('panel_id', $panelId);

            $label = "[{$permissionKey}] (panel: {$panelId})";
        }

        $count = $query->count();

        if ($count === 0) {
            $this->warn("No grants found: {$label} for user #{$userId}.");

            return self::SUCCESS;
        }

        if (! $force && ! $this->confirm("Delete {$count} grant(s) {$label} for user #{$userId}?")) {
            $this->line('Cancelled.');

            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("Revoked {$deleted} grant(s): {$label} for user #{$userId}.");

        return self::SUCCESS;
    }
}
