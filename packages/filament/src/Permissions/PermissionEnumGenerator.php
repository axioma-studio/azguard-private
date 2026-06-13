<?php

declare(strict_types=1);

namespace AzGuard\Filament\Permissions;

use Illuminate\Support\Str;

/**
 * Builds the source of a backed permission enum for one {@see PermissionSubject}.
 *
 * The enum value is the short key ("{resource}.{ability}"); the owning panel
 * prefixes it with the panel id when resolving, so the generated enum is
 * panel-agnostic and picked up by EnumPermissionCatalogBuilder.
 */
final readonly class PermissionEnumGenerator
{
    public function className(PermissionSubject $subject): string
    {
        return Str::studly($subject->name).'Permission';
    }

    public function source(PermissionSubject $subject, string $namespace): string
    {
        $class = $this->className($subject);
        $resource = Str::snake($subject->name);

        $cases = array_map(
            fn (string $ability): string => sprintf(
                "    case %s = '%s.%s';",
                Str::studly($ability),
                $resource,
                $ability,
            ),
            $subject->abilities,
        );

        $body = implode("\n", $cases);

        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace {$namespace};

            enum {$class}: string
            {
            {$body}
            }

            PHP;
    }
}
