<?php

declare(strict_types=1);

namespace AzGuard\Support;

use AzGuard\Registry\Resolver\EffectivePermissionResolver;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Thin adapter that isolates the core package from packages/context.
 *
 * Checks for the azguard/context package via class_exists — core therefore
 * has NO hard dependency on context and works without it.
 *
 * Methods never throw; they return false when the context package is absent
 * or when any error occurs.
 *
 * Previously named AzGuardContextBridge. The old class is kept as a
 * @deprecated alias for backwards compatibility.
 */
final class AzGuardContextProxy
{
    private const CONTEXT_MANAGER = 'AzGuard\\Context\\AuthorizationContextManager';
    private const CONTEXT_CLASS   = 'AzGuard\\Context\\AuthorizationContext';

    /**
     * One-off check with an arbitrary $context object.
     *
     * $context must have public fields contextType and contextId.
     * panelId is taken from $context->panelId if present, otherwise from $panelId.
     *
     * Does NOT mutate the global AuthorizationContextManager.
     *
     * @param  object  $context  duck-typed: {contextType: string, contextId: int|string, panelId?: string}
     */
    public static function checkWithContext(
        Authenticatable $user,
        string $permission,
        string $panelId,
        object $context,
    ): bool {
        if (! class_exists(self::CONTEXT_MANAGER)) {
            // context package not installed — fall back to global permission check
            return app(EffectivePermissionResolver::class)
                ->forUser($user, $panelId)
                ->grants($permission);
        }

        try {
            $effectivePanelId = property_exists($context, 'panelId')
                ? $context->panelId
                : $panelId;

            $contextObj = app(self::CONTEXT_CLASS, [
                'panelId'     => $effectivePanelId,
                'contextType' => $context->contextType,
                'contextId'   => $context->contextId,
            ]);

            return self::resolveWithIsolatedContext($user, $permission, $effectivePanelId, $contextObj);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * One-off check with explicit contextType + contextId.
     * This is the primary method called from hasPermissionIn().
     */
    public static function checkInContext(
        Authenticatable $user,
        string $contextType,
        int|string $contextId,
        string $permission,
        string $panelId,
    ): bool {
        if (! class_exists(self::CONTEXT_MANAGER)) {
            return false;
        }

        try {
            $contextObj = app(self::CONTEXT_CLASS, [
                'panelId'     => $panelId,
                'contextType' => $contextType,
                'contextId'   => $contextId,
            ]);

            return self::resolveWithIsolatedContext($user, $permission, $panelId, $contextObj);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Temporarily sets the context, resolves permissions, then restores the previous context.
     *
     * Flushes the request-level resolver cache for the user so that the fresh
     * context is reflected in the result, then restores it after the check.
     *
     * @param  object  $contextObj  AuthorizationContext instance
     */
    private static function resolveWithIsolatedContext(
        Authenticatable $user,
        string $permission,
        string $panelId,
        object $contextObj,
    ): bool {
        /** @var object $manager */
        $manager  = app(self::CONTEXT_MANAGER);
        $resolver = app(EffectivePermissionResolver::class);

        // Save the current context so we can restore it in the finally block.
        $previous = $manager->current($panelId);

        $manager->set($contextObj);
        $resolver->forgetForUser($user, $panelId);

        try {
            $result = $resolver->forUser($user, $panelId)->grants($permission);
        } finally {
            // Always restore previous state — even if an exception is thrown.
            if ($previous !== null) {
                $manager->set($previous);
            } else {
                $manager->clear($panelId);
            }

            $resolver->forgetForUser($user, $panelId);
        }

        return $result;
    }
}
