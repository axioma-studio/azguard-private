<?php

declare(strict_types=1);

namespace AzGuard\Context;

use AzGuard\Contracts\ContextGuard as ContextGuardContract;
use AzGuard\Contracts\PermissionResolverInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Override;

/**
 * Context-package implementation of {@see ContextGuardContract}.
 *
 * Temporarily sets the requested context, resolves the user's effective
 * permissions through the normal grant-source chain, then always restores
 * the previous context — even if resolution throws.
 */
final readonly class ContextGuard implements ContextGuardContract
{
    public function __construct(
        private AuthorizationContextManager $manager,
        private PermissionResolverInterface $resolver,
    ) {}

    #[Override]
    public function checkInContext(
        Authenticatable $user,
        string $contextType,
        int|string $contextId,
        string $permission,
        string $panelId,
    ): bool {
        $context = new AuthorizationContext($panelId, $contextType, $contextId);
        $previous = $this->manager->current($panelId);

        $this->manager->set($context);
        $this->resolver->forgetForUser($user, $panelId);

        try {
            return $this->resolver->forUser($user, $panelId)->grants($permission);
        } finally {
            $previous instanceof AuthorizationContext
                ? $this->manager->set($previous)
                : $this->manager->clear($panelId);

            $this->resolver->forgetForUser($user, $panelId);
        }
    }
}
