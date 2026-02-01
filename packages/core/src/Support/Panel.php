<?php

namespace AzGuard\Support;

class Panel
{
    protected string $id;
    protected string $path;
    protected string $namespace;

    protected bool $isScopedByPanelId = true; // По умолчанию включено


    public static function make(): static
    {
        return new static();
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

    // Автоматически устанавливается провайдером
    public function setNamespace(string $namespace): static
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function scopedByPanelId(bool $condition = true): static
    {
        $this->isScopedByPanelId = $condition;
        return $this;
    }

    public function getPermissionName(string $permission): string
    {
        // Если включено, возвращаем "admin.post.view", иначе "post.view"
        return $this->isScopedByPanelId
            ? "{$this->id}.{$permission}"
            : $permission;
    }
}
