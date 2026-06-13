<?php

declare(strict_types=1);

namespace AzGuard\Filament\Commands;

use AzGuard\Filament\Permissions\PermissionDiscovery;
use AzGuard\Filament\Permissions\PermissionEnumGenerator;
use AzGuard\Filament\Permissions\PermissionSchema;
use AzGuard\Filament\Permissions\PermissionSubject;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Generates the permission schema for a Filament panel from config.
 *
 *   php artisan azguard:filament:generate
 *   php artisan azguard:filament:generate --source=enum --panel=admin --dry-run
 *
 * `database` source just reports the keys (registered at runtime); `enum`
 * writes one backed permission enum per resource into the configured path.
 */
final class GenerateFilamentPermissionsCommand extends Command
{
    protected $signature = 'azguard:filament:generate
        {--source= : Override the configured source (database|enum)}
        {--panel= : Override the configured AzGuard panel id}
        {--dry-run : Show what would be written without touching the filesystem}';

    protected $description = 'Generate the AzGuard permission schema for a Filament panel';

    public function handle(
        PermissionDiscovery $discovery,
        PermissionSchema $schema,
        PermissionEnumGenerator $generator,
        Filesystem $files,
    ): int {
        $panelId = (string) ($this->option('panel') ?: config('az-guard-filament.panel', 'admin'));
        $source = (string) ($this->option('source') ?: config('az-guard-filament.source', 'database'));

        $subjects = $discovery->subjects($panelId);

        if ($subjects === []) {
            $this->warn("No Filament resources or pages discovered for panel [{$panelId}].");

            return self::SUCCESS;
        }

        return match ($source) {
            'database' => $this->report($panelId, $schema, $subjects),
            'enum' => $this->writeEnums($subjects, $generator, $files),
            default => $this->unsupported($source),
        };
    }

    /**
     * @param  list<PermissionSubject>  $subjects
     */
    private function report(string $panelId, PermissionSchema $schema, array $subjects): int
    {
        $rows = [];

        foreach ($subjects as $subject) {
            foreach ($schema->keys($panelId, $subject) as $key) {
                $rows[] = [$subject->label, $key];
            }
        }

        $this->info(sprintf('Permission schema for panel [%s] — %d keys (database source):', $panelId, count($rows)));
        $this->table(['Group', 'Permission'], $rows);
        $this->line('These keys are registered in the catalog automatically; grant them to roles in the Role UI.');

        return self::SUCCESS;
    }

    /**
     * @param  list<PermissionSubject>  $subjects
     */
    private function writeEnums(array $subjects, PermissionEnumGenerator $generator, Filesystem $files): int
    {
        $namespace = (string) config('az-guard-filament.generation.enum_namespace', 'App\\Guards\\Admin\\Permissions');
        $path = base_path((string) config('az-guard-filament.generation.enum_path', 'app/Guards/Admin/Permissions'));
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! $files->isDirectory($path)) {
            $files->makeDirectory($path, recursive: true);
        }

        $written = 0;

        foreach ($subjects as $subject) {
            // Enums describe model-backed resources; pages stay on the DB source.
            if ($subject->model === null) {
                continue;
            }

            $class = $generator->className($subject);
            $file = $path.DIRECTORY_SEPARATOR.$class.'.php';

            if ($dryRun) {
                $this->line("would write: {$file}");

                continue;
            }

            $files->put($file, $generator->source($subject, $namespace));
            $this->line("wrote: {$class}");
            $written++;
        }

        $this->info($dryRun
            ? 'Dry run complete.'
            : "Generated {$written} permission enum(s) in {$namespace}.");

        return self::SUCCESS;
    }

    private function unsupported(string $source): int
    {
        $this->error("Unsupported source [{$source}]. Use 'database' or 'enum'.");

        return self::FAILURE;
    }
}
