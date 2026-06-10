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
 * Builds permission catalog entries from policy methods annotated with #[GateAbility].
 *
 * When $policyClasses are provided explicitly (via PanelProvider::registerCatalogBuilders()),
 * those classes are used directly. Otherwise falls back to filesystem discovery.
 */
final class PolicyAbilityCatalogBuilder implements PermissionCatalogBuilder
{
    /**
     * @param  string|null  $panelId  When set, this builder only handles this panel.
     * @param  list<class-string>  $policyClasses  Explicit policy class list (optional).
     */
    public function __construct(
        private readonly ?string $panelId = null,
        private readonly array $policyClasses = [],
    ) {}

    #[Override]
    public function build(string $panelId): array
    {
        $panel = AzGuard::panel($panelId);

        if ($panel === null) {
            return [];
        }

        $classes = match (true) {
            $this->policyClasses !== [] => $this->policyClasses,
            $this->panelId !== null => [],
            default => $this->discoverPolicyClasses($panel->getBasePath(), $panel->getNamespace()),
        };

        $definitions = [];

        foreach ($classes as $policyClass) {
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
        if ($this->panelId !== null) {
            return $this->panelId === $panelId;
        }

        return AzGuard::panel($panelId) !== null;
    }

    /**
     * @return list<class-string>
     */
    private function discoverPolicyClasses(string $basePath, string $baseNamespace): array
    {
        if ($basePath === '' || $baseNamespace === '') {
            return [];
        }

        return (new PolicyDiscovery)->discoverPolicyClasses(
            basePath: $basePath,
            baseNamespace: $baseNamespace,
        );
    }
}
