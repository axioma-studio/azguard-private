<?php

declare(strict_types=1);

namespace AzGuard\Concerns;

/**
 * Convenience trait that composes HasRoles and HasPermissions.
 * Use this on your User model for the full AzGuard API.
 * You can instead use HasRoles or HasPermissions individually
 * if you only need a subset of the functionality.
 */
trait HasAzGuard
{
    use HasPermissions, HasRoles;
}
