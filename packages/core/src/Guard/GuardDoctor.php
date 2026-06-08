<?php

declare(strict_types=1);

namespace AzGuard\Guard;

/**
 * @deprecated Use AzGuardDiagnostics instead. Will be removed in the next major version.
 *
 * BC alias — keeps existing code that type-hints or resolves GuardDoctor
 * from the container working without changes.
 *
 * Migration:
 *   - Replace `GuardDoctor` with `AzGuardDiagnostics` everywhere.
 *   - The class_alias below makes both names interchangeable at runtime.
 */
class_alias(AzGuardDiagnostics::class, GuardDoctor::class);
