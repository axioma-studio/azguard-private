<?php

declare(strict_types=1);

namespace AzGuard\Commands;

use AzGuard\Commands\Concerns\ResolvesGuardNamespaces;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class MakeGuardAbilitiesCommand extends Command
{
    use ResolvesGuardNamespaces;

    protected $signature = 'make:guard-abilities
        {panel : Панель (App)}
        {domain : Домен (Documents)}
        {--path=app/Guards}';

    protected $description = 'Создаёт Abilities DTO на базе AbilitiesDto';

    public function handle(): int
    {
        $panel = (string) $this->argument(key: 'panel');
        $domain = (string) $this->argument(key: 'domain');
        $pathOption = (string) $this->option(key: 'path');

        $basePath = $this->guardBasePath(path: $pathOption, panel: $panel);
        $baseNamespace = $this->guardBaseNamespace(path: $pathOption, panel: $panel);
        $abilitiesPath = $this->domainPath(basePath: $basePath, domain: $domain).'/Abilities/'.$domain.'Abilities.php';

        if (File::exists(path: $abilitiesPath)) {
            $this->error("Abilities уже существуют: {$abilitiesPath}");

            return self::FAILURE;
        }

        File::ensureDirectoryExists(directory: dirname(path: $abilitiesPath));

        $stub = File::get(path: __DIR__.'/../../stubs/panel/domain-abilities.stub');
        $replacements = [
            'namespace' => $baseNamespace,
            'domain' => $domain,
            'panelId' => strtolower(string: $panel),
        ];

        foreach ($replacements as $key => $value) {
            $stub = str_replace(search: '{{ '.$key.' }}', replace: $value, subject: $stub);
        }

        File::put(path: $abilitiesPath, contents: $stub);
        $this->info("Abilities созданы: {$abilitiesPath}");

        return self::SUCCESS;
    }
}
