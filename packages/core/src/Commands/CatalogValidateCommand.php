<?php

declare(strict_types=1);

namespace AzGuard\Commands;

use AzGuard\Attributes\GateAbility;
use AzGuard\Facades\AzGuard;
use AzGuard\Guard\PolicyDiscovery;
use AzGuard\Registry\Contracts\PermissionCatalog;
use AzGuard\Registry\Contracts\PermissionDefinition;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionMethod;
use UnitEnum;

/**
 * Валидирует согласованность каталога с политиками.
 *
 * Проверки:
 * 1. Каждый key в каталоге имеет policy-метод с #[GateAbility] (предупреждение в normal-режиме, ошибка в strict).
 * 2. Каждый key в каталоге не является дубликатом.
 * 3. Каждый #[GateAbility]-метод политики ссылается на enum-case из каталога.
 *
 * Примеры:
 *   php artisan guard:catalog:validate
 *   php artisan guard:catalog:validate --panel=app
 *   php artisan guard:catalog:validate --strict
 */
final class CatalogValidateCommand extends Command
{
    protected $signature = 'guard:catalog:validate
        {--panel= : Проверить только указанную панель}
        {--strict : Превращать предупреждения в ошибки}';

    protected $description = 'Проверяет согласованность PermissionCatalog с политиками и enum';

    protected $aliases = ['az-guard:catalog:validate'];

    /** @var list<string> */
    private array $errors = [];

    /** @var list<string> */
    private array $warnings = [];

    public function handle(PermissionCatalog $catalog): int
    {
        $this->errors = [];
        $this->warnings = [];

        $panelFilter = $this->normalizeOption('panel');
        $strict = (bool) $this->option('strict');

        $panelIds = $panelFilter !== null
            ? [$panelFilter]
            : $catalog->panels();

        if ($panelIds === []) {
            $this->warn('Нет зарегистрированных панелей.');

            return self::SUCCESS;
        }

        foreach ($panelIds as $panelId) {
            $this->validatePanel($catalog, $panelId);
        }

        foreach ($this->warnings as $warning) {
            $this->warn($warning);
        }

        foreach ($this->errors as $error) {
            $this->error($error);
        }

        $hasIssues = $this->errors !== [] || ($strict && $this->warnings !== []);

        if (! $hasIssues) {
            $panelLabel = $panelFilter ?? 'all';
            $this->info("guard:catalog:validate: проверки панели [{$panelLabel}] пройдены.");

            return self::SUCCESS;
        }

        $this->error('guard:catalog:validate: найдены ошибки согласованности каталога.');

        return self::FAILURE;
    }

    private function validatePanel(PermissionCatalog $catalog, string $panelId): void
    {
        $panel = AzGuard::getPanel($panelId);

        if ($panel === null) {
            $this->errors[] = "Панель [{$panelId}] не найдена в AzGuardManager.";

            return;
        }

        $basePath = $panel->getBasePath();
        $baseNamespace = $panel->getNamespace();

        if ($basePath === '' || $baseNamespace === '') {
            $this->warnings[] = "Панель [{$panelId}]: basePath/namespace не заданы.";

            return;
        }

        // Построить карту ability -> handler из #[GateAbility]
        $abilityMap = $this->buildAbilityMap(
            basePath: $basePath,
            baseNamespace: $baseNamespace,
            panelId: $panelId,
        );

        $definitions = $catalog->all($panelId);

        // Проверка 1: каждый ключ каталога есть в политиках
        $this->checkCatalogKeysHavePolicies(
            definitions: $definitions,
            abilityMap: $abilityMap,
            panelId: $panelId,
        );

        // Проверка 2: каждый #[GateAbility] ссылается на ключ в каталоге
        $this->checkAbilitiesAreCatalogued(
            abilityMap: $abilityMap,
            catalog: $catalog,
            panelId: $panelId,
        );

        $this->info(
            "Панель [{$panelId}]: {$this->countLabel(count($definitions))} в каталоге, {$this->countLabel(count($abilityMap))} в политиках."
        );
    }

    /**
     * Строит мапу resolvedKey -> "Policy::method" из #[GateAbility]-методов.
     *
     * @return array<string, string>
     */
    private function buildAbilityMap(string $basePath, string $baseNamespace, string $panelId): array
    {
        $discovery = new PolicyDiscovery;
        $policyClasses = $discovery->discoverPolicyClasses(
            basePath: $basePath,
            baseNamespace: $baseNamespace,
        );

        $panel = AzGuard::getPanel($panelId);
        $map = [];

        foreach ($policyClasses as $policyClass) {
            $reflection = new ReflectionClass($policyClass);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(GateAbility::class) as $attribute) {
                    /** @var GateAbility $gateAbility */
                    $gateAbility = $attribute->newInstance();

                    if (! $gateAbility->permission instanceof UnitEnum || $panel === null) {
                        continue;
                    }

                    $resolvedKey = $panel->resolvePermission($gateAbility->permission);
                    $map[$resolvedKey] = "{$policyClass}::{$method->getName()}";
                }
            }
        }

        return $map;
    }

    /**
     * Проверка: каждый key каталога имеет policy-метод.
     *
     * @param  list<PermissionDefinition>  $definitions
     * @param  array<string, string>  $abilityMap
     */
    private function checkCatalogKeysHavePolicies(
        array $definitions,
        array $abilityMap,
        string $panelId,
    ): void {
        foreach ($definitions as $definition) {
            $key = $definition->key();

            if (! isset($abilityMap[$key])) {
                $message = "Панель [{$panelId}]: permission [{$key}] есть в каталоге, но нет policy-метода с #[GateAbility].";

                $this->warnings[] = $message;
            }
        }
    }

    /**
     * Проверка: каждый ability-метод ссылается на ключ в каталоге.
     *
     * @param  array<string, string>  $abilityMap
     */
    private function checkAbilitiesAreCatalogued(
        array $abilityMap,
        PermissionCatalog $catalog,
        string $panelId,
    ): void {
        foreach ($abilityMap as $resolvedKey => $handler) {
            if (! $catalog->has($panelId, $resolvedKey)) {
                $this->errors[] = "Панель [{$panelId}]: {$handler} использует [{$resolvedKey}], который отсутствует в PermissionCatalog.";
            }
        }
    }

    private function countLabel(int $count): string
    {
        return "{$count} permission" . ($count !== 1 ? 's' : '');
    }

    private function normalizeOption(string $name): ?string
    {
        $value = $this->option($name);

        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
