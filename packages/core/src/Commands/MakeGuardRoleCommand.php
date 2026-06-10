<?php

declare(strict_types=1);

namespace AzGuard\Commands;

use AzGuard\Facades\AzGuard;
use AzGuard\PanelProvider;
use AzGuard\Support\Panel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class MakeGuardRoleCommand extends Command
{
    protected $signature = 'make:guard-role';

    protected $description = 'Create a new role for a specific panel';

    public function handle(): void
    {
        $panels = AzGuard::getPanels();

        if (empty($panels)) {
            $this->warn('No registered panels found.');
            $this->line('1. Create a panel: <info>php artisan make:guard-panel</info>');
            $this->line('2. Register it in <info>config/az-guard.php</info>');

            return;
        }

        $panelIds = array_keys($panels);
        $selectedId = $this->choice('Which panel should the role belong to?', $panelIds, 0);

        $providerClass = $this->getProviderClassById($selectedId);

        if (! $providerClass) {
            $this->error("Provider for panel [{$selectedId}] not found in the container.");

            return;
        }

        $roleName = $this->ask('Role name (e.g. Editor)');
        $roleClass = Str::studly($roleName);

        $reflection = new ReflectionClass($providerClass);
        $panelPath = dirname($reflection->getFileName());
        $panelNamespace = $reflection->getNamespaceName();

        $targetPath = "{$panelPath}/Roles/{$roleClass}Role.php";

        if (File::exists($targetPath)) {
            $this->error("Role [{$roleClass}] already exists in this panel.");

            return;
        }

        File::ensureDirectoryExists("{$panelPath}/Roles");

        $this->generateFile($targetPath, [
            'namespace' => $panelNamespace,
            'name' => $roleClass,
            'resLower' => $selectedId.'.base',
        ]);

        $this->info("Role created: {$targetPath}");
    }

    protected function getProviderClassById(string $id): ?string
    {
        foreach (app()->getLoadedProviders() as $providerClass => $bool) {
            if (is_subclass_of($providerClass, PanelProvider::class)) {
                $instance = app()->getProvider($providerClass);

                if ($instance->panel(Panel::make())->getId() === $id) {
                    return $providerClass;
                }
            }
        }

        return null;
    }

    protected function generateFile(string $path, array $replacements): void
    {
        $stubPath = __DIR__.'/../../stubs/panel/role.stub';
        $content = File::get($stubPath);

        foreach ($replacements as $key => $value) {
            $content = str_replace("{{ $key }}", $value, $content);
        }

        File::put($path, $content);
    }
}
