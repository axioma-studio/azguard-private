<?php

namespace AzGuard\Guard;

use Illuminate\Support\Facades\File;
use AzGuard\Contracts\RoleInterface;

class DiscoveryService
{
    /**
     * Сканирует директорию и возвращает массив имен классов, реализующих RoleInterface.
     */
    public function discoverRoles(string $path, string $namespace): array
    {
        if (!is_dir($path)) {
            return [];
        }

        return collect(File::allFiles($path))
            ->map(function ($file) use ($namespace) {
                $relativePath = $file->getRelativePathname();
                return $namespace . str_replace(['/', '.php'], ['\\', ''], $relativePath);
            })
            ->filter(fn($class) => class_exists($class) && is_subclass_of($class, RoleInterface::class))
            ->toArray();
    }
}
