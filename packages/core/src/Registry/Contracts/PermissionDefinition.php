<?php

declare(strict_types=1);

namespace AzGuard\Registry\Contracts;

/**
 * Иммутабельный дескриптор одного permission из каталога.
 * Источник истины — всегда PHP-код (enum, attribute).
 */
interface PermissionDefinition
{
    /**
     * Fully-resolved key: "app.documents.view"
     */
    public function key(): string;

    /**
     * Short key без префикса панели: "documents.view"
     */
    public function shortKey(): string;

    /**
     * ID панели: "app", "admin"
     */
    public function panelId(): string;

    /**
     * Группа для UI/CLI: "Documents", "Dashboard"
     */
    public function group(): ?string;

    /**
     * Метаданные для Filament / TypeScript-генерации.
     */
    public function meta(): PermissionMeta;

    /**
     * Whether the key is a dynamic pattern such as app.team.{id}.admin.
     */
    public function isDynamic(): bool;
}
