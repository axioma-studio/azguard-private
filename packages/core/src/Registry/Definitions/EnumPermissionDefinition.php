<?php

declare(strict_types=1);

namespace AzGuard\Registry\Definitions;

use AzGuard\Registry\Contracts\PermissionDefinition;
use AzGuard\Registry\Contracts\PermissionMeta;
use UnitEnum;

/**
 * PermissionDefinition, построенный из backed enum case.
 *
 * Пример:
 *   DocumentsPermission::View → key "app.documents.view"
 */
final class EnumPermissionDefinition implements PermissionDefinition
{
    public function __construct(
        private readonly string $resolvedKey,
        private readonly string $panelId,
        private readonly ?string $group,
        private readonly PermissionMeta $meta,
        private readonly string $enumClass,
        private readonly string $caseName,
    ) {}

    /**
     * Создать из enum case и панели.
     * Логика resolvePermission берётся из Panel::resolvePermission().
     */
    public static function fromCase(UnitEnum $case, string $panelId, string $resolvedKey, ?string $group = null): self
    {
        return new self(
            resolvedKey: $resolvedKey,
            panelId: $panelId,
            group: $group ?? self::inferGroupFromClass($case::class),
            meta: new SimplePermissionMeta(
                label: self::formatLabel($case->name),
            ),
            enumClass: $case::class,
            caseName: $case->name,
        );
    }

    public function key(): string
    {
        return $this->resolvedKey;
    }

    public function shortKey(): string
    {
        $prefix = $this->panelId . '.';

        return str_starts_with($this->resolvedKey, $prefix)
            ? substr($this->resolvedKey, strlen($prefix))
            : $this->resolvedKey;
    }

    public function panelId(): string
    {
        return $this->panelId;
    }

    public function group(): ?string
    {
        return $this->group;
    }

    public function meta(): PermissionMeta
    {
        return $this->meta;
    }

    public function isDynamic(): bool
    {
        return false;
    }

    public function enumClass(): string
    {
        return $this->enumClass;
    }

    public function caseName(): string
    {
        return $this->caseName;
    }

    /**
     * Инферить группу из имени enum-класса: DocumentsPermission → Documents
     */
    private static function inferGroupFromClass(string $enumClass): string
    {
        $short = class_basename($enumClass);

        return str_ends_with($short, 'Permission')
            ? substr($short, 0, -strlen('Permission'))
            : $short;
    }

    /**
     * Форматировать label из PascalCase имени case: ViewAny → View Any
     */
    private static function formatLabel(string $caseName): string
    {
        return trim(preg_replace('/([A-Z])/', ' $1', $caseName));
    }
}
