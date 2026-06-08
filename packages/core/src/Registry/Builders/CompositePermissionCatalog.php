<?php

declare(strict_types=1);

namespace AzGuard\Registry\Builders;

use AzGuard\Registry\Contracts\PermissionCatalog;
use AzGuard\Registry\Contracts\PermissionCatalogBuilder;
use AzGuard\Registry\Contracts\PermissionDefinition;
use AzGuard\Registry\Exceptions\InvalidCatalogException;
use AzGuard\Registry\Exceptions\InvalidPermissionKeyException;

/**
 * Агрегирует несколько PermissionCatalogBuilder в единый каталог.
 *
 * При дублировании ключа из разных источников проверяет совпадение:
 * - если resolvedKey идентичен — silently dedupe (enum + policy = одна запись).
 * - если conflicting definition (разные источники, разные группы) — InvalidCatalogException.
 */
final class CompositePermissionCatalog implements PermissionCatalog
{
    /** @var array<string, array<string, PermissionDefinition>> panelId => key => definition */
    private array $definitions = [];

    /** @var array<string, array<string, string>> panelId => key => builderClass (для error reporting) */
    private array $sources = [];

    private bool $built = false;

    /**
     * @param list<PermissionCatalogBuilder> $builders
     * @param list<string> $panelIds
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
                        // Одинаковый ключ из двух builder'ов — допустимо (enum + policy на тот же case).
                        // Конфликт только если группы различаются (признак разных definition).
                        $existing = $this->definitions[$panelId][$key];
                        if ($existing->group() !== $definition->group()) {
                            throw InvalidCatalogException::duplicateKey(
                                key: $key,
                                panelId: $panelId,
                                source1: $this->sources[$panelId][$key],
                                source2: $builderClass,
                            );
                        }

                        // Тихий dedupe — оставляем первую запись
                        continue;
                    }

                    $this->definitions[$panelId][$key] = $definition;
                    $this->sources[$panelId][$key] = $builderClass;
                }
            }
        }

        $this->built = true;
    }

    public function all(string $panelId): array
    {
        $this->ensureBuilt();

        return array_values($this->definitions[$panelId] ?? []);
    }

    public function has(string $panelId, string $resolvedKey): bool
    {
        $this->ensureBuilt();

        return isset($this->definitions[$panelId][$resolvedKey]);
    }

    public function get(string $panelId, string $resolvedKey): ?PermissionDefinition
    {
        $this->ensureBuilt();

        return $this->definitions[$panelId][$resolvedKey] ?? null;
    }

    public function assert(string $panelId, string $resolvedKey): PermissionDefinition
    {
        $this->ensureBuilt();

        return $this->definitions[$panelId][$resolvedKey]
            ?? throw InvalidPermissionKeyException::forKey($resolvedKey, $panelId);
    }

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

    public function panels(): array
    {
        return $this->panelIds;
    }

    /**
     * Сброс кэша каталога (для тестов или hot-reload в dev).
     */
    public function flush(): void
    {
        $this->definitions = [];
        $this->sources = [];
        $this->built = false;
    }
}
