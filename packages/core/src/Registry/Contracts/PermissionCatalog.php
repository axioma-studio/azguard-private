<?php

declare(strict_types=1);

namespace AzGuard\Registry\Contracts;

use AzGuard\Registry\Exceptions\InvalidPermissionKeyException;

/**
 * Registry of all known permissions.
 * Single source of truth: no key reaches the database without
 * passing through catalog->assert().
 */
interface PermissionCatalog
{
    /**
     * All definitions for a panel.
     *
     * @return list<PermissionDefinition>
     */
    public function all(string $panelId): array;

    /**
     * Whether the resolved key exists in the catalog.
     */
    public function has(string $panelId, string $resolvedKey): bool;

    /**
     * Returns the definition or null if not found.
     */
    public function get(string $panelId, string $resolvedKey): ?PermissionDefinition;

    /**
     * Returns the definition or throws if not found.
     *
     * @throws InvalidPermissionKeyException
     */
    public function assert(string $panelId, string $resolvedKey): PermissionDefinition;

    /**
     * Definitions grouped by group().
     *
     * @return array<string, list<PermissionDefinition>>
     */
    public function groups(string $panelId): array;

    /**
     * All registered panel IDs.
     *
     * @return list<string>
     */
    public function panels(): array;
}
