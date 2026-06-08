<?php

declare(strict_types=1);

namespace AzGuard\Registry\Contracts;

use AzGuard\Registry\Exceptions\InvalidPermissionKeyException;

/**
 * Реестр всех известных permissions.
 * Единственный источник истины: никакой ключ не попадает в БД
 * без прохождения через catalog->assert().
 */
interface PermissionCatalog
{
    /**
     * Все definitions для панели.
     *
     * @return list<PermissionDefinition>
     */
    public function all(string $panelId): array;

    /**
     * Проверить наличие ключа в каталоге.
     */
    public function has(string $panelId, string $resolvedKey): bool;

    /**
     * Получить definition или null.
     */
    public function get(string $panelId, string $resolvedKey): ?PermissionDefinition;

    /**
     * Получить definition или бросить исключение.
     *
     * @throws InvalidPermissionKeyException
     */
    public function assert(string $panelId, string $resolvedKey): PermissionDefinition;

    /**
     * Definitions, сгруппированные по group().
     *
     * @return array<string, list<PermissionDefinition>>
     */
    public function groups(string $panelId): array;

    /**
     * Список всех зарегистрированных panelId.
     *
     * @return list<string>
     */
    public function panels(): array;
}
