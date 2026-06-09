<?php

declare(strict_types=1);

namespace AzGuard\Registry\Builders;

use AzGuard\Attributes\GateAbility;
use AzGuard\Facades\AzGuard;
use AzGuard\Guard\PolicyDiscovery;
use AzGuard\Registry\Contracts\PermissionCatalogBuilder;
use AzGuard\Registry\Definitions\EnumPermissionDefinition;
use Override;
use ReflectionClass;
use ReflectionMethod;
use UnitEnum;

/**
 * Строит каталог из методов политик с атрибутом #[GateAbility].
 * Используется для перекрёстной проверки с EnumPermissionCatalogBuilder
 * (пересечение = канонический каталог).
 */
final class PolicyAbilityCatalogBuilder implements PermissionCatalogBuilder
{
    #[Override]
    public function build(string $panelId): array
    {
        $panel = AzGuard::getPanel($panelId);

        if ($panel === null) {
            return [];
        }

        $basePath = $panel->getBasePath();
        $baseNamespace = $panel->getNamespace();

        if ($basePath === '' || $baseNamespace === '') {
            return [];
        }

        $discovery = new PolicyDiscovery;
        $policyClasses = $discovery->discoverPolicyClasses(
            basePath: $basePath,
            baseNamespace: $baseNamespace,
        );

        $definitions = [];

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

                    $resolvedKey = $panel->resolvePermission($permission);

                    $definitions[] = EnumPermissionDefinition::fromCase(
                        case: $permission,
                        panelId: $panelId,
                        resolvedKey: $resolvedKey,
                    );
                }
            }
        }

        return $definitions;
    }

    #[Override]
    public function supports(string $panelId): bool
    {
        return AzGuard::getPanel($panelId) !== null;
    }
}
