<?php

declare(strict_types=1);

namespace AzGuard\Commands;

use AzGuard\Commands\Concerns\ResolvesGuardNamespaces;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class MakeGuardPermissionCommand extends Command
{
    use ResolvesGuardNamespaces;

    protected $signature = 'make:guard-permission
        {panel : Панель (App)}
        {domain : Домен (Documents)}
        {name? : Имя case (View)}
        {--path=app/Guards}';

    protected $description = 'Добавляет case в enum Permissions или создаёт enum';

    public function handle(): int
    {
        $panel = (string) $this->argument(key: 'panel');
        $domain = (string) $this->argument(key: 'domain');
        $caseName = $this->argument(key: 'name');
        $pathOption = (string) $this->option(key: 'path');

        $enumPath = $this->domainPath(
            basePath: $this->guardBasePath(path: $pathOption, panel: $panel),
            domain: $domain,
        ).'/Permissions/'.$domain.'Permission.php';

        if (! File::exists(path: $enumPath)) {
            $this->call(command: 'make:guard-panel', arguments: [
                'panel' => $panel,
                'domain' => $domain,
                '--path' => $pathOption,
            ]);

            return self::SUCCESS;
        }

        if (! is_string($caseName) || $caseName === '') {
            $this->error('Укажите имя case: make:guard-permission App Documents Export');

            return self::FAILURE;
        }

        $domainKey = $this->domainKey(domain: $domain);
        $caseKey = Str::snake(value: $caseName);
        $enumCase = "    case {$caseName} = '{$domainKey}.{$caseKey}';\n";

        $content = File::get(path: $enumPath);
        $content = str_replace(
            search: "\n    case ",
            replace: "\n".$enumCase."    case ",
            subject: $content,
            count: 1,
        );

        File::put(path: $enumPath, contents: $content);
        $this->info("Добавлен case {$caseName} в {$enumPath}");

        return self::SUCCESS;
    }
}
