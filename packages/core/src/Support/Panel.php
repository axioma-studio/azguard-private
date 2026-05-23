<?php

declare(strict_types=1);

namespace AzGuard\Support;

final class Panel
{
    protected string $id = '';

    protected string $path = '';

    protected string $namespace = '';

    protected string $basePath = '';

    protected bool $isScopedByPanelId = true;

    public static function make(): static
    {
        return new static;
    }

    public function id(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function path(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function setNamespace(string $namespace): static
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function setBasePath(string $basePath): static
    {
        $this->basePath = $basePath;

        return $this;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function scopedByPanelId(bool $condition = true): static
    {
        $this->isScopedByPanelId = $condition;

        return $this;
    }

    public function getPermissionName(string $permission): string
    {
        return $this->isScopedByPanelId
            ? "{$this->id}.{$permission}"
            : $permission;
    }

    public function resolvePermission(string|\UnitEnum $permission): string
    {
        if ($permission instanceof \BackedEnum) {
            return $this->getPermissionName(permission: $permission->value);
        }

        if ($permission instanceof \UnitEnum) {
            return $this->getPermissionName(permission: $permission->name);
        }

        return $this->getPermissionName(permission: $permission);
    }
}
