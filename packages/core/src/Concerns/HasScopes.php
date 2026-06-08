<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

/**
 * @deprecated Use HasScopedRoles instead.
 *
 * HasScopes conflicted with Laravel's own "scope" terminology for
 * Eloquent query scopes (globalScope, localScope). This alias is kept
 * for backward compatibility and will be removed in the next major version.
 *
 * Migration:
 *   - Replace `use HasScopes;` with `use HasScopedRoles;` in your models.
 *   - Replace `use AzGuard\Concerns\HasScopes;` with
 *     `use AzGuard\Concerns\HasScopedRoles;` in your imports.
 */
trait HasScopes
{
    use HasScopedRoles;

    /**
     * Boot alias so models that were booted via the old trait name
     * continue to work without code changes.
     *
     * Laravel discovers boot methods by trait name: bootHasScopes() is
     * called automatically when the model boots, which in turn triggers
     * bootHasScopedRoles() through the trait.
     */
    public static function bootHasScopes(): void
    {
        static::bootHasScopedRoles();
    }
}
