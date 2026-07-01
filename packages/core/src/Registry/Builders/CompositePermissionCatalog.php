<?php

declare(strict_types=1);

namespace AzGuard\Registry\Builders;

use AzGuard\Registry\Contracts\PermissionCatalog;
use AzGuard\Registry\Contracts\PermissionCatalogBuilder;
use AzGuard\Registry\Contracts\PermissionDefinition;
use AzGuard\Registry\Exceptions\InvalidCatalogException;
use AzGuard\Registry\Exceptions\InvalidPermissionKeyException;
use Override;

/**
 * Aggregates multiple PermissionCatalogBuilders into a single catalog.
 *
 * When a key is duplicated across sources, it checks for a match:
 * - if resolvedKey is identical — silently dedupe (enum + policy = one entry).
 * - if the definition conflicts (different sources, different groups) — InvalidCatalogException.
 */
final class CompositePermissionCatalog implements PermissionCatalog
{
    /** @var array<string, array<string, PermissionDefinition>> panelId => key => definition */
    private array $definitions = [];

    /** @var array<string, array<string, string>> panelId => key => builderClass (for error reporting) */
    private array $sources = [];

    private bool $built = false;

    /**
     * @param  list<PermissionCatalogBuilder>  $builders
     * @param  list<string>  $panelIds
     */
    public function __construct(
        private readonly array $builders,
        private readonly array $panelIds,
    ) {}

    private function ensureBuilt(): void
    {
        if ($this->built) {
            return;
        }

        foreach ($this->panelIds as $panelId) {
            $this->definitions[$panelId] = [];
            $this->sources[$panelId] = [];

            foreach ($this->builders as $builder) {
                if (! $builder->supports($panelId)) {
                    continue;
                }

                foreach ($builder->build($panelId) as $definition) {
                    $key = $definition->key();
                    $builderClass = $builder::class;

                    if (isset($this->definitions[$panelId][$key])) {
                        // Same key from two builders — allowed (enum + policy on the same case).
                        // A conflict only if the groups differ (a sign of different definitions).
                        $existing = $this->definitions[$panelId][$key];

                        if ($existing->group() !== $definition->group()) {
                            throw InvalidCatalogException::duplicateKey(
                                key: $key,
                                panelId: $panelId,
                                source1: $this->sources[$panelId][$key],
                                source2: $builderClass,
                            );
                        }

                        // Silent dedupe — keep the first entry
                        continue;
                    }

                    $this->definitions[$panelId][$key] = $definition;
                    $this->sources[$panelId][$key] = $builderClass;
                }
            }
        }

        $this->built = true;
    }

    #[Override]
    public function all(string $panelId): array
    {
        $this->ensureBuilt();

        return array_values($this->definitions[$panelId] ?? []);
    }

    #[Override]
    public function has(string $panelId, string $resolvedKey): bool
    {
        $this->ensureBuilt();

        return isset($this->definitions[$panelId][$resolvedKey]);
    }

    #[Override]
    public function get(string $panelId, string $resolvedKey): ?PermissionDefinition
    {
        $this->ensureBuilt();

        return $this->definitions[$panelId][$resolvedKey] ?? null;
    }

    #[Override]
    public function assert(string $panelId, string $resolvedKey): PermissionDefinition
    {
        $this->ensureBuilt();

        return $this->definitions[$panelId][$resolvedKey]
            ?? throw InvalidPermissionKeyException::forKey($resolvedKey, $panelId);
    }

    #[Override]
    public function groups(string $panelId): array
    {
        $this->ensureBuilt();

        $grouped = [];

        foreach ($this->definitions[$panelId] ?? [] as $definition) {
            $group = $definition->group() ?? 'Other';
            $grouped[$group][] = $definition;
        }

        ksort($grouped);

        return $grouped;
    }

    #[Override]
    public function panels(): array
    {
        return $this->panelIds;
    }

    /**
     * Reset the catalog cache (for tests or hot-reload in dev).
     */
    public function flush(): void
    {
        $this->definitions = [];
        $this->sources = [];
        $this->built = false;
    }
}
