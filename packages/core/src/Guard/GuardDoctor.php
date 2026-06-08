<?php

declare(strict_types=1);

namespace AzGuard\Guard;

/**
 * @deprecated Use DiagnosticsService instead.
 *
 * BC alias kept so existing code referencing GuardDoctor still compiles.
 * Will be removed in the next major version.
 */
class GuardDoctor extends DiagnosticsService
{
}
