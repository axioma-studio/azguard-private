<?php

declare(strict_types=1);

namespace AzGuard\Commands;

use AzGuard\Commands\Concerns\ResolvesGuardNamespaces;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class MakeGuardPanelCommand extends Command
{
    use ResolvesGuardNamespaces;

    protected $signature = 'make:guard-panel
        {panel : Panel name (e.g. App)}
        {domain=Documents : Domain inside the panel}
        {--path=app/Guards : Base path}
        {--role=Admin : Initial role name}
        {--with-abilities : Also generate an Abilities DTO}';

    protected $description = 'Scaffold a guard panel with Permissions/Policies/Abilities domain structure';

    public function handle(): int
    {
        $panel = (string) $this->argument(key: 'panel');
        $domain = (string) $this->argument(key: 'domain');
        $pathOption = (string) $this->option(key: 'path');
        $roleName = (string) $this->option(key: 'role');
        $withAbilities = (bool) $this->option(key: 'with-abilities');

        $basePath = $this->guardBasePath(path: $pathOption, panel: $panel);
        $baseNamespace = $this->guardBaseNamespace(path: $pathOption, panel: $panel);
        $panelId = Str::lower(value: $panel);
        $domainKey = $this->domainKey(domain: $domain);

        if (File::isDirectory(directory: $basePath)) {
            $this->error("Panel already exists: {$basePath}");

            return self::FAILURE;
        }

        File::makeDirectory(path: "{$basePath}/Roles", mode: 0755, recursive: true);
        File::makeDirectory(path: $this->domainPath(basePath: $basePath, domain: $domain).'/Permissions', mode: 0755, recursive: true);
        File::makeDirectory(path: $this->domainPath(basePath: $basePath, domain: $domain).'/Policies', mode: 0755, recursive: true);

        if ($withAbilities) {
            File::makeDirectory(path: $this->domainPath(basePath: $basePath, domain: $domain).'/Abilities', mode: 0755, recursive: true);
        }

        $replacements = [
            'namespace' => $baseNamespace,
            'panel' => $panel,
            'panelId' => $panelId,
            'domain' => $domain,
            'domainKey' => $domainKey,
            'name' => $roleName,
            'nameLower' => Str::lower(value: $roleName),
        ];

        $this->generateFile(
            path: $basePath,
            filename: "{$panel}GuardPanelProvider.php",
            stubName: 'guardpanelprovider',
            replacements: $replacements,
        );
        $this->generateFile(
            path: "{$basePath}/Roles",
            filename: "{$roleName}Role.php",
            stubName: 'role',
            replacements: $replacements,
        );
        $this->generateFile(
            path: $this->domainPath(basePath: $basePath, domain: $domain).'/Permissions',
            filename: "{$domain}Permission.php",
            stubName: 'domain-permission',
            replacements: $replacements,
        );
        $this->generateFile(
            path: $this->domainPath(basePath: $basePath, domain: $domain).'/Policies',
            filename: "{$domain}Policy.php",
            stubName: 'domain-policy',
            replacements: $replacements,
        );

        if ($withAbilities) {
            $this->generateFile(
                path: $this->domainPath(basePath: $basePath, domain: $domain).'/Abilities',
                filename: "{$domain}Abilities.php",
                stubName: 'domain-abilities',
                replacements: $replacements,
            );
        }

        $this->info("Panel [{$panel}] created at {$basePath}");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $replacements
     */
    protected function generateFile(string $path, string $filename, string $stubName, array $replacements): void
    {
        $stubPath = __DIR__.'/../../stubs/panel/'.$stubName.'.stub';

        if (! File::exists(path: $stubPath)) {
            $this->warn("Stub not found: {$stubName}");

            return;
        }

        $content = File::get(path: $stubPath);

        foreach ($replacements as $key => $value) {
            $content = str_replace(search: '{{ '.$key.' }}', replace: $value, subject: $content);
        }

        File::put(path: "{$path}/{$filename}", contents: $content);
    }
}
