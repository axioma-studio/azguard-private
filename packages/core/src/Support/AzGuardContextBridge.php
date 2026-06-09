<?php

declare(strict_types=1);

namespace AzGuard\Support;

/**
 * @deprecated Use AzGuardContextProxy instead. Will be removed in the next major version.
 *
 * BC alias — keeps existing code that calls AzGuardContextBridge::checkWithContext()
 * or AzGuardContextBridge::checkInContext() working without changes.
 *
 * Migration:
 *   - Replace `AzGuardContextBridge` with `AzGuardContextProxy` everywhere.
 */
class_alias(AzGuardContextProxy::class, AzGuardContextBridge::class);
