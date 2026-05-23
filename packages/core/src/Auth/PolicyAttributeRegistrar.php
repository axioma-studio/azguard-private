<?php

declare(strict_types=1);

namespace AzGuard\Auth;

use AzGuard\Attributes\GateAbility;
use AzGuard\Support\Panel;
use Illuminate\Support\Facades\Gate;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

final class PolicyAttributeRegistrar
{
    /**
     * @param  list<class-string>  $policyClasses
     */
    public function register(array $policyClasses, Panel $panel): void
    {
        $registeredAbilities = [];

        foreach ($policyClasses as $policyClass) {
            $reflection = new ReflectionClass($policyClass);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(GateAbility::class) as $attribute) {
                    /** @var GateAbility $gateAbility */
                    $gateAbility = $attribute->newInstance();
                    $ability = $panel->resolvePermission(permission: $gateAbility->permission);

                    if (isset($registeredAbilities[$ability])) {
                        throw new RuntimeException(
                            message: "Gate ability '{$ability}' already registered by {$registeredAbilities[$ability]}",
                        );
                    }

                    $registeredAbilities[$ability] = "{$policyClass}::{$method->getName()}";

                    Gate::define(
                        ability: $ability,
                        callback: [$policyClass, $method->getName()],
                    );
                }
            }
        }
    }
}
