<?php

declare(strict_types=1);

namespace AzGuard\Registry\Contracts;

/**
 * Строит список PermissionDefinition для указанной панели.
 * Каждый builder покрывает один источник: enum, #[GateAbility], config.
 */
interface PermissionCatalogBuilder
{
    /**
     * @return list<PermissionDefinition>
     */
    public function build(string $panelId): array;

    /**
     * Поддерживает ли builder данную панель.
     * CompositePermissionCatalog вызывает build() только если supports() === true.
     */
    public function supports(string $panelId): bool;
}
