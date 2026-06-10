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
 * Canonical diagnostics class. Two BC aliases exist for historical names:
 *
 * @see GuardDoctor     — alias, kept for code that used the pre-rename class name
 * @see DiagnosticsService — alias, kept for code that used the interim duplicate
 *
 * Both aliases will be removed in the next major version.
 */
final class AzGuardDiagnostics
{
    /** @var list<string> */
    private array $errors = [];

    /** @var list<string> */
    private array $warnings = [];

    /**
     * Run all diagnostic checks for the configured panels.
     *
     * @param  string|null  $panelFilter  When provided, only the matching panel is checked.
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

            $discovery    = new PolicyDiscovery;
            $policyClasses = $discovery->discoverPolicyClasses(
                basePath: $basePath,
                baseNamespace: $baseNamespace,
            );

            // Discover permission enums once per panel — reused by three checks below.
            $enumClasses = $this->discoverPermissionEnums($basePath, $baseNamespace);

            $registeredAbilities = $this->collectRegisteredAbilities(
                policyClasses: $policyClasses,
                panel: $panel,
            );

            foreach ($registeredAbilities as $ability => $handler) {
                $abilityRows[] = ['panel' => $panelId, 'ability' => $ability, 'handler' => $handler];
            }

            $this->checkDuplicateAbilities(abilities: $registeredAbilities, panelId: $panelId);

            $this->checkEnumsAgainstPolicies(
                panel: $panel,
                registeredAbilities: $registeredAbilities,
                enumClasses: $enumClasses,
            );

            $this->checkGateAbilityEnumReferences(
                policyClasses: $policyClasses,
                enumClasses: $enumClasses,
            );

            $this->checkRoles(
                basePath: $basePath,
                baseNamespace: $baseNamespace,
                panel: $panel,
                knownAbilities: array_merge(
                    array_keys($registeredAbilities),
                    $this->collectRoleOnlyAbilities(
                        panel: $panel,
                        enumClasses: $enumClasses,
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

    // -------------------------------------------------------------------------
    //  Private helpers
    // -------------------------------------------------------------------------

    /** @param list<class-string> $policyClasses @return array<string, string> */
    private function collectRegisteredAbilities(array $policyClasses, Panel $panel): array
    {
        $abilities = [];

        foreach ($policyClasses as $policyClass) {
            $reflection = new ReflectionClass($policyClass);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(GateAbility::class) as $attribute) {
                    /** @var GateAbility $ga */
                    $ga      = $attribute->newInstance();
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

    /**
     * @param array<string, string> $registeredAbilities
     * @param list<class-string>    $enumClasses
     */
    private function checkEnumsAgainstPolicies(
        Panel $panel,
        array $registeredAbilities,
        array $enumClasses,
    ): void {
        foreach ($enumClasses as $enumClass) {
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

    /**
     * @param list<class-string> $policyClasses
     * @param list<class-string> $enumClasses    Pre-discovered for this panel — avoids double FS scan.
     */
    private function checkGateAbilityEnumReferences(
        array $policyClasses,
        array $enumClasses,
    ): void {
        // Build a hashset for O(1) membership checks instead of repeated in_array().
        $enumIndex = array_fill_keys($enumClasses, true);

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

                    if (! isset($enumIndex[$enumClass])) {
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
        $rolesPath = $basePath.'/Roles';

        if (! is_dir($rolesPath)) {
            return;
        }

        // Build a hashset for O(1) membership check in the inner loop.
        $knownIndex = array_fill_keys($knownAbilities, true);

        foreach (File::files($rolesPath) as $file) {
            if (! str_ends_with($file->getFilename(), 'Role.php')) {
                continue;
            }

            $class = $baseNamespace.'\\Roles\\'.str_replace('.php', '', $file->getFilename());

            if (! class_exists($class)) {
                continue;
            }

            if (! is_subclass_of($class, RoleInterface::class)) {
                continue;
            }

            /** @var RoleInterface $role */
            $role = app()->make($class);

            foreach ($role->permissions() as $permission) {
                if ($permission === '*') {
                    continue;
                }

                if (! isset($knownIndex[$permission])) {
                    $this->errors[] = "Role {$class}: unknown permission [{$permission}].";
                }

                if (! str_starts_with((string) $permission, $panel->getId().'.')) {
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

    /**
     * @param list<class-string> $enumClasses
     * @return list<string>
     */
    private function collectRoleOnlyAbilities(
        Panel $panel,
        array $enumClasses,
    ): array {
        $abilities = [];

        foreach ($enumClasses as $enumClass) {
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

    /**
     * Walk the panel's base path and collect all *Permission.php enum classes.
     *
     * @return list<class-string>
     */
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

            $class = $baseNamespace.'\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

            if (class_exists($class) && (new ReflectionClass($class))->isEnum()) {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}
