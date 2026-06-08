<?php

declare(strict_types=1);

namespace AzGuard\Context\Exceptions;

use RuntimeException;

/**
 * Выбрасывается DenyWithoutContextStrategy, когда контекст обязателен,
 * но не был установлен через SetAuthorizationContext middleware.
 */
final class MissingAuthorizationContextException extends RuntimeException
{}
