<?php

declare(strict_types=1);

namespace AzGuard\Guard;

use AzGuard\Attributes\GateAbility;
use AzGuard\Attributes\RoleOnly;
use AzGuard\Contracts\RoleInterface;
use AzGuard\Facades\AzGuard;
use AzGuard\Support\Panel;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionEnum;
use ReflectionMethod;
use UnitEnum;

/**
 * Inspects AzGuard panel configuration for mismatches, duplicates and orphans.
 *
 * Replaces the misleadingly-named GuardDoctor (which is not a Laravel Guard).
 * GuardDoctor is kept as a BC alias.
 *
 * @see GuardDoctor
 */
final class DiagnosticsService
{
    /** @var list<string> */
    private array $errors = [];

    /** @var list<string> */
    private array $warnings = [];

    /**
     * @return array{errors: list<string>, warnings: list<string>, abilities: list<array{panel: string, ability: string, handler: string}>}
     */
    public function diagnose(?string $panelFilter = null): array
    {
        $this->errors = [];
        $this->warnings = [];
        $abilityRows = [];

        foreach (AzGuard::getPanels() as $panelId => $panel) {
            if ($panelFilter !== null && $panelFilter !== $panelId) {
                continue;
            }

            $basePath      = $panel->getBasePath();
            $baseNamespace = $panel->getNamespace();

            if ($basePath === '' || $baseNamespace === '') {
                $this->warnings[] = "Panel [{$panelId}]: basePath/namespace not set in provider.";
                continue;
            }

            $discovery   = new PolicyDiscovery;
            $policyClasses = $discovery->discoverPolicyClasses(
                basePath: $basePath,
                baseNamespace: $baseNamespace,
            );

            $registeredAbilities = $this->collectRegisteredAbilities(
                policyClasses: $policyClasses,
                panel: $panel,
            );

            foreach ($registeredAbilities as $ability => $handler) {
                $abilityRows[] = ['panel' => $panelId, 'ability' => $ability, 'handler' => $handler];
            }

            $this->checkDuplicateAbilities(abilities: $registeredAbilities, panelId: $panelId);
            $this->checkEnumsAgainstPolicies(
                basePath: $basePath,
                baseNamespace: $baseNamespace,
                panel: $panel,
                registeredAbilities: $registeredAbilities,
            );
            $this->checkGateAbilityEnumReferences(
                policyClasses: $policyClasses,
                basePath: $basePath,
                baseNamespace: $baseNamespace,
            );
            $this->checkRoles(
                basePath: $basePath,
                baseNamespace: $baseNamespace,
                panel: $panel,
                knownAbilities: array_merge(
                    array_keys($registeredAbilities),
                    $this->collectRoleOnlyAbilities(
                        basePath: $basePath,
                        baseNamespace: $baseNamespace,
                        panel: $panel,
                    ),
                ),
            );
            $this->checkOrphanPolicies(policyClasses: $policyClasses, panelId: $panelId);
        }

        return [
            'errors'    => $this->errors,
            'warnings'  => $this->warnings,
            'abilities' => $abilityRows,
        ];
    }

    /** @param list<class-string> $policyClasses @return array<string, string> */
    private function collectRegisteredAbilities(array $policyClasses, Panel $panel): array
    {
        $abilities = [];

        foreach ($policyClasses as $policyClass) {
            $reflection = new ReflectionClass($policyClass);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(GateAbility::class) as $attribute) {
                    /** @var GateAbility $ga */
                    $ga = $attribute->newInstance();
                    $ability = $panel->resolvePermission(permission: $ga->permission);
                    $abilities[$ability] = "{$policyClass}::{$method->getName()}";
                }
            }
        }

        return $abilities;
    }

    /** @param array<string, string> $abilities */
    private function checkDuplicateAbilities(array $abilities, string $panelId): void
    {
        $seen = [];

        foreach ($abilities as $ability => $handler) {
            if (isset($seen[$ability])) {
                $this->errors[] = "Panel [{$panelId}]: duplicate ability [{$ability}] — {$seen[$ability]} and {$handler}.";
                continue;
            }

            $seen[$ability] = $handler;
        }
    }

    /** @param array<string, string> $registeredAbilities */
    private function checkEnumsAgainstPolicies(
        string $basePath,
        string $baseNamespace,
        Panel $panel,
        array $registeredAbilities,
    ): void {
        foreach ($this->discoverPermissionEnums($basePath, $baseNamespace) as $enumClass) {
            foreach ((new ReflectionEnum($enumClass))->getCases() as $case) {
                if ($case->getAttributes(RoleOnly::class) !== []) {
                    continue;
                }

                /** @var UnitEnum $enumCase */
                $enumCase = $case->getValue();
                $resolved = $panel->resolvePermission(permission: $enumCase);

                if (! isset($registeredAbilities[$resolved])) {
                    $this->errors[] = "Enum {$enumClass}::{$case->getName()} → [{$resolved}] has no #[GateAbility] policy method.";
                }
            }
        }
    }

    /** @param list<class-string> $policyClasses */
    private function checkGateAbilityEnumReferences(
        array $policyClasses,
        string $basePath,
        string $baseNamespace,
    ): void {
        $enumClasses = $this->discoverPermissionEnums($basePath, $baseNamespace);

        foreach ($policyClasses as $policyClass) {
            $reflection = new ReflectionClass($policyClass);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(GateAbility::class) as $attribute) {
                    /** @var GateAbility $ga */
                    $ga = $attribute->newInstance();

                    if (! $ga->permission instanceof UnitEnum) {
                        continue;
                    }

                    $enumClass = $ga->permission::class;

                    if (! in_array($enumClass, $enumClasses, strict: true)) {
                        $this->errors[] = "{$policyClass}::{$method->getName()}: permission enum {$enumClass} not found in panel Permissions/.";
                    }
                }
            }
        }
    }

    /** @param list<string> $knownAbilities */
    private function checkRoles(
        string $basePath,
        string $baseNamespace,
        Panel $panel,
        array $knownAbilities,
    ): void {
        $rolesPath = $basePath . '/Roles';

        if (! is_dir($rolesPath)) {
            return;
        }

        foreach (File::files($rolesPath) as $file) {
            if (! str_ends_with($file->getFilename(), 'Role.php')) {
                continue;
            }

            $class = $baseNamespace . '\\Roles\\' . str_replace('.php', '', $file->getFilename());

            if (! class_exists($class) || ! is_subclass_of($class, RoleInterface::class)) {
                continue;
            }

            /** @var RoleInterface $role */
            $role = app()->make($class);

            foreach ($role->permissions() as $permission) {
                if ($permission === '*') {
                    continue;
                }

                if (! in_array($permission, $knownAbilities, strict: true)) {
                    $this->errors[] = "Role {$class}: unknown permission [{$permission}].";
                }

                if (! str_starts_with($permission, $panel->getId() . '.')) {
                    $this->warnings[] = "Role {$class}: [{$permission}] missing panel prefix [{$panel->getId()}.].";
                }
            }
        }
    }

    /** @param list<class-string> $policyClasses */
    private function checkOrphanPolicies(array $policyClasses, string $panelId): void
    {
        foreach ($policyClasses as $policyClass) {
            $reflection = new ReflectionClass($policyClass);
            $hasAbility = false;

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getAttributes(GateAbility::class) !== []) {
                    $hasAbility = true;
                    break;
                }
            }

            if (! $hasAbility) {
                $this->warnings[] = "Panel [{$panelId}]: policy {$policyClass} has no #[GateAbility] methods.";
            }
        }
    }

    /** @return list<string> */
    private function collectRoleOnlyAbilities(string $basePath, string $baseNamespace, Panel $panel): array
    {
        $abilities = [];

        foreach ($this->discoverPermissionEnums($basePath, $baseNamespace) as $enumClass) {
            foreach ((new ReflectionEnum($enumClass))->getCases() as $case) {
                if ($case->getAttributes(RoleOnly::class) === []) {
                    continue;
                }

                /** @var UnitEnum $enumCase */
                $enumCase  = $case->getValue();
                $abilities[] = $panel->resolvePermission(permission: $enumCase);
            }
        }

        return $abilities;
    }

    /** @return list<class-string> */
    private function discoverPermissionEnums(string $basePath, string $baseNamespace): array
    {
        if (! is_dir($basePath)) {
            return [];
        }

        $classes = [];

        foreach (File::allFiles($basePath) as $file) {
            if (! str_ends_with($file->getFilename(), 'Permission.php')) {
                continue;
            }

            $class = $baseNamespace . '\\' . str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

            if (class_exists($class) && (new ReflectionClass($class))->isEnum()) {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}
