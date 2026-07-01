<?php

declare(strict_types=1);

namespace AzGuard\Guard;

use AzGuard\Contracts\RoleInterface;
use Illuminate\Support\Facades\File;

class DiscoveryService
{
    /**
     * Scans a directory and returns an array of class names implementing RoleInterface.
     */
    public function discoverRoles(string $path, string $namespace): array
    {
        if (! is_dir($path)) {
            return [];
        }

        return collect(File::allFiles($path))
            ->map(function ($file) use ($namespace) {
                $relativePath = $file->getRelativePathname();

                return $namespace.str_replace(['/', '.php'], ['\\', ''], $relativePath);
            })
            ->filter(fn ($class): bool => class_exists($class) && is_subclass_of($class, RoleInterface::class))
            ->toArray();
    }
}
