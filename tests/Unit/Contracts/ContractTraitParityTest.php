<?php

declare(strict_types=1);

use AzGuard\Contracts\AzGuardUser;
use AzGuard\Contracts\HasDirectGrants;
use AzGuard\Contracts\HasPermissions;
use AzGuard\Contracts\HasRoles;
use AzGuard\Contracts\HasScopedRoles;
use Illuminate\Contracts\Auth\Access\Authorizable;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

/**
 * The public contracts in AzGuard\Contracts mirror the AzGuard\Concerns traits
 * 1:1. This guards against drift: if a trait method's signature changes but the
 * contract is not updated (or vice-versa), a consumer's `implements` would break.
 * We assert every contract method exists on the paired trait with an identical
 * normalized signature.
 */
$normalizeType = function (?ReflectionType $type): string {
    if ($type === null) {
        return 'mixed';
    }

    if ($type instanceof ReflectionUnionType) {
        $parts = array_map(static fn (ReflectionType $t): string => $t instanceof ReflectionNamedType ? $t->getName() : (string) $t, $type->getTypes());
        sort($parts);

        return implode('|', $parts);
    }

    if ($type instanceof ReflectionNamedType) {
        return ($type->allowsNull() && $type->getName() !== 'null' && $type->getName() !== 'mixed' ? '?' : '').$type->getName();
    }

    return (string) $type;
};

$signature = function (ReflectionMethod $method) use ($normalizeType): string {
    $params = array_map(
        static function ($p) use ($normalizeType): string {
            return $normalizeType($p->getType()).' $'.$p->getName().($p->isVariadic() ? '...' : '').($p->isDefaultValueAvailable() ? '=' : '');
        },
        $method->getParameters(),
    );

    return implode(', ', $params).' : '.$normalizeType($method->getReturnType());
};

$pairs = [
    HasPermissions::class => AzGuard\Concerns\HasPermissions::class,
    HasRoles::class => AzGuard\Concerns\HasRoles::class,
    HasScopedRoles::class => AzGuard\Concerns\HasScopedRoles::class,
    HasDirectGrants::class => AzGuard\Concerns\HasDirectGrants::class,
];

foreach ($pairs as $contract => $trait) {
    it("trait {$trait} satisfies every method of contract {$contract}", function () use ($contract, $trait, $signature) {
        $traitReflection = new ReflectionClass($trait);

        foreach ((new ReflectionClass($contract))->getMethods() as $contractMethod) {
            expect($traitReflection->hasMethod($contractMethod->getName()))
                ->toBeTrue("trait {$trait} is missing contract method {$contractMethod->getName()}()");

            $traitMethod = $traitReflection->getMethod($contractMethod->getName());

            expect($signature($traitMethod))
                ->toBe($signature($contractMethod), "signature drift on {$contractMethod->getName()}()");
        }
    });
}

it('AzGuardUser composes the permission and role contracts and Authorizable', function () {
    $interfaces = class_implements(AzGuardUser::class);

    expect($interfaces)
        ->toHaveKey(HasPermissions::class)
        ->toHaveKey(HasRoles::class)
        ->toHaveKey(Authorizable::class);
});
