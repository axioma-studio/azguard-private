<?php

declare(strict_types=1);

namespace AzGuard\Abilities;

use AzGuard\Support\ResolvesGateAbilities;

abstract readonly class AbilitiesDto
{
    use ResolvesGateAbilities;

    /**
     * @return array<string, string>
     */
    abstract protected static function abilityMap(): array;

    /**
     * @param  array<int, mixed>  $arguments
     * @return array<string, bool>
     */
    protected static function resolveFlags(array $arguments = []): array
    {
        return static::resolveGateFlags(
            abilityMap: static::abilityMap(),
            arguments: $arguments,
        );
    }

    /**
     * @return array<string, bool>
     */
    public function toArray(): array
    {
        return get_object_vars(object: $this);
    }
}
