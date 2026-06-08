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

final class GuardDoctor
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

            $basePath = $panel->getBasePath();
            $baseNamespace = $panel->getNamespace();

            if ($basePath === '' || $baseNamespace === '') {
                $this->warnings[] = "Panel [{$panelId}]: basePath or namespace is not configured on the provider.";

                continue;
            }

            $discovery = new PolicyDiscovery;
            $policyClasses = $discovery->discoverPolicyClasses(
                basePath: $basePath,
                baseNamespace: $baseNamespace,
            );

            $registeredAbilities = $this->collectRegisteredAbilities(
                policyClasses: $policyClasses,
                panel: $panel,
            );

            foreach ($registeredAbilities as $ability => $handler) {
                $abilityRows[] = [
                    'panel'   => $panelId,
                    'ability' => $ability,
                    'handler' => $handler,
                ];
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

    /**
     * @param  list<class-string>  $policyClasses
     * @return array<string, string>
     */
    private function collectRegisteredAbilities(array $policyClasses, Panel $panel): array
    {
        $abilities = [];

        foreach ($policyClasses as $policyClass) {
            $reflection = new ReflectionClass($policyClass);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(GateAbility::class) as $attribute) {
                    /** @var GateAbility $gateAbility */
                    $gateAbility = $attribute->newInstance();
                    $ability = $panel->resolvePermission(permission: $gateAbility->permission);
                    $abilities[$ability] = "{$policyClass}::{$method->getName()}";
                }
            }
        }

        return $abilities;
    }

    /**
     * @param  array<string, string>  $abilities
     */
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
     * @param  array<string, string>  $registeredAbilities
     */
    private function checkEnumsAgainstPolicies(
        string $basePath,
        string $baseNamespace,
        Panel $panel,
        array $registeredAbilities,
    ): void {
        foreach ($this->discoverPermissionEnums(basePath: $basePath, baseNamespace: $baseNamespace) as $enumClass) {
            foreach ((new ReflectionEnum($enumClass))->getCases() as $case) {
                if ($case->getAttributes(RoleOnly::class) !== []) {
                    continue;
                }

                /** @var UnitEnum $enumCase */
                $enumCase = $case->getValue();
                $resolved = $panel->resolvePermission(permission: $enumCase);

                if (! isset($registeredAbilities[$resolved])) {
                    $this->errors[] = "Enum {$enumClass}::{$case->getName()} → [{$resolved}] has no policy method annotated with #[GateAbility].";
                }
            }
        }
    }

    /**
     * @param  list<class-string>  $policyClasses
     */
    private function checkGateAbilityEnumReferences(
        array $policyClasses,
        string $basePath,
        string $baseNamespace,
    ): void {
        $enumClasses = $this->discoverPermissionEnums(basePath: $basePath, baseNamespace: $baseNamespace);

        foreach ($policyClasses as $policyClass) {
            $reflection = new ReflectionClass($policyClass);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(GateAbility::class) as $attribute) {
                    /** @var GateAbility $gateAbility */
                    $gateAbility = $attribute->newInstance();
                    $permission = $gateAbility->permission;

                    if (! $permission instanceof UnitEnum) {
                        continue;
                    }

                    $enumClass = $permission::class;

                    if (! in_array($enumClass, $enumClasses, strict: true)) {
                        $this->errors[] = "{$policyClass}::{$method->getName()}: permission enum {$enumClass} was not found in the panel's Permissions/ directory.";
                    }
                }
            }
        }
    }

    /**
     * @param  list<string>  $knownAbilities
     */
    private function checkRoles(string $basePath, string $baseNamespace, Panel $panel, array $knownAbilities): void
    {
        $rolesPath = $basePath . '/Roles';

        if (! is_dir($rolesPath)) {
            return;
        }

        foreach (File::files($rolesPath) as $file) {
            if (! str_ends_with(haystack: $file->getFilename(), needle: 'Role.php')) {
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
                    $this->errors[] = "Role {$class}: references unknown permission [{$permission}].";
                }

                if (! str_starts_with(haystack: $permission, needle: $panel->getId() . '.')) {
                    $this->warnings[] = "Role {$class}: permission [{$permission}] is missing the panel prefix [{$panel->getId()}.].";
                }
            }
        }
    }

    /**
     * @param  list<class-string>  $policyClasses
     */
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
                $this->warnings[] = "Panel [{$panelId}]: policy {$policyClass} has no public methods annotated with #[GateAbility].";
            }
        }
    }

    /**
     * @return list<string>
     */
    private function collectRoleOnlyAbilities(string $basePath, string $baseNamespace, Panel $panel): array
    {
        $abilities = [];

        foreach ($this->discoverPermissionEnums(basePath: $basePath, baseNamespace: $baseNamespace) as $enumClass) {
            foreach ((new ReflectionEnum($enumClass))->getCases() as $case) {
                if ($case->getAttributes(RoleOnly::class) === []) {
                    continue;
                }

                /** @var UnitEnum $enumCase */
                $enumCase = $case->getValue();
                $abilities[] = $panel->resolvePermission(permission: $enumCase);
            }
        }

        return $abilities;
    }

    /**
     * @return list<class-string>
     */
    private function discoverPermissionEnums(string $basePath, string $baseNamespace): array
    {
        if (! is_dir($basePath)) {
            return [];
        }

        $classes = [];

        foreach (File::allFiles(directory: $basePath) as $file) {
            if (! str_ends_with(haystack: $file->getFilename(), needle: 'Permission.php')) {
                continue;
            }

            $relativePath = $file->getRelativePathname();
            $class = $baseNamespace . '\\' . str_replace(['/', '.php'], ['\\', ''], $relativePath);

            if (class_exists($class) && (new ReflectionClass($class))->isEnum()) {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}
