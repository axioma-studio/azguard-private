<?php

declare(strict_types=1);

namespace AzGuard\Registry\Builders;

use AzGuard\Facades\AzGuard;
use AzGuard\Registry\Contracts\PermissionCatalogBuilder;
use AzGuard\Registry\Contracts\PermissionDefinition;
use AzGuard\Registry\Definitions\EnumPermissionDefinition;
use Illuminate\Support\Facades\File;
use Override;
use ReflectionClass;
use ReflectionEnum;
use UnitEnum;

/**
 * Строит каталог из backed enum'ов *Permission.php в папке Permissions/ панели.
 * Логика discovery аналогична GuardDoctor::discoverPermissionEnums(),
 * но возвращает типизированные PermissionDefinition вместо сырых строк.
 */
final class EnumPermissionCatalogBuilder implements PermissionCatalogBuilder
{
    #[Override]
    public function build(string $panelId): array
    {
        $panel = AzGuard::getPanel($panelId);

        if ($panel === null) {
            return [];
        }

        $basePath = $panel->getBasePath();
        $baseNamespace = $panel->getNamespace();

        if ($basePath === '' || $baseNamespace === '') {
            return [];
        }

        $definitions = [];

        foreach ($this->discoverEnumClasses($basePath, $baseNamespace) as $enumClass) {
            $reflection = new ReflectionEnum($enumClass);

            foreach ($reflection->getCases() as $case) {
                /** @var UnitEnum $enumCase */
                $enumCase = $case->getValue();
                $resolvedKey = $panel->resolvePermission($enumCase);

                $definitions[] = EnumPermissionDefinition::fromCase(
                    case: $enumCase,
                    panelId: $panelId,
                    resolvedKey: $resolvedKey,
                );
            }
        }

        return $definitions;
    }

    #[Override]
    public function supports(string $panelId): bool
    {
        return AzGuard::getPanel($panelId) !== null;
    }

    /**
     * @return list<class-string>
     */
    private function discoverEnumClasses(string $basePath, string $baseNamespace): array
    {
        if (! is_dir($basePath)) {
            return [];
        }

        $classes = [];

        foreach (File::allFiles($basePath) as $file) {
            if (! str_ends_with($file->getFilename(), 'Permission.php')) {
                continue;
            }

            $relative = $file->getRelativePathname();
            $class = $baseNamespace.'\\'.str_replace(['/', '.php'], ['\\', ''], $relative);

            if (class_exists($class) && (new ReflectionClass($class))->isEnum()) {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}
