<?php

declare(strict_types=1);

namespace AzGuard\Commands;

use AzGuard\Facades\AzGuard;
use AzGuard\Guard\PolicyDiscovery;
use AzGuard\Attributes\GateAbility;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionMethod;

class ListPermissionsCommand extends Command
{
    protected $signature = 'azguard:list-permissions {panel? : ID панели (оставьте пустым для всех)}';

    protected $description = 'Вывести все зарегистрированные разрешения AzGuard по панелям';

    public function handle(): int
    {
        $panelFilter = $this->argument('panel');
        $panels = AzGuard::getPanels();

        if (empty($panels)) {
            $this->warn('Нет зарегистрированных панелей AzGuard.');
            return self::SUCCESS;
        }

        foreach ($panels as $panelId => $panel) {
            if ($panelFilter !== null && $panelFilter !== $panelId) {
                continue;
            }

            $this->line('');
            $this->info("Панель: <comment>{$panelId}</comment>");

            $basePath = $panel->getBasePath();
            $baseNamespace = $panel->getNamespace();

            if ($basePath === '' || $baseNamespace === '') {
                $this->warn("  Не задан basePath/namespace для панели [{$panelId}].");
                continue;
            }

            $discovery = new PolicyDiscovery;
            $policyClasses = $discovery->discoverPolicyClasses(
                basePath: $basePath,
                baseNamespace: $baseNamespace,
            );

            $rows = [];
            foreach ($policyClasses as $policyClass) {
                $reflection = new ReflectionClass($policyClass);
                foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    foreach ($method->getAttributes(GateAbility::class) as $attribute) {
                        /** @var GateAbility $ga */
                        $ga = $attribute->newInstance();
                        $ability = $panel->resolvePermission(permission: $ga->permission);
                        $rows[] = [$ability, $policyClass . '::' . $method->getName()];
                    }
                }
            }

            if (empty($rows)) {
                $this->line('  Разрешений не найдено.');
                continue;
            }

            $this->table(['Разрешение', 'Обработчик (Policy::method)'], $rows);
        }

        $this->line('');

        return self::SUCCESS;
    }
}
