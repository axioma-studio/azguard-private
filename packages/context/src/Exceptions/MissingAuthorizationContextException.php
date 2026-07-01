<?php

declare(strict_types=1);

namespace AzGuard\Context\Exceptions;

use RuntimeException;

/**
 * Thrown by DenyWithoutContextStrategy when a context is required
 * but was not set via the SetAuthorizationContext middleware.
 */
final class MissingAuthorizationContextException extends RuntimeException {}
