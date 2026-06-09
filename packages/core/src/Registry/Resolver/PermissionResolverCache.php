<?php

declare(strict_types=1);

namespace AzGuard\Registry\Resolver;

/**
 * @deprecated Use PermissionCache instead.
 *
 * Kept as a BC alias so existing bindings, mocks, and
 * app-level extends continue to work without changes.
 *
 * Migration:
 *   Replace PermissionResolverCache with PermissionCache
 *   in all type-hints, service container bindings, and tests.
 */
class PermissionResolverCache extends PermissionCache {}
