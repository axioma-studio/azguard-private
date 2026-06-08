<?php

declare(strict_types=1);

namespace AzGuard\Context\Contracts;

use Illuminate\Http\Request;

/**
 * Резолвер контекста из HTTP-запроса.
 *
 * Реализуется в приложении и регистрируется через config:
 *   'context_resolver' => App\Auth\WorkspaceContextResolver::class
 *
 * Пример реализации:
 *
 *   class WorkspaceContextResolver implements ResolvesContext
 *   {
 *       public function resolve(Request $request): ?AuthorizationContextInterface
 *       {
 *           $workspaceId = $request->route('workspace');
 *           return $workspaceId
 *               ? new AuthorizationContext('workspace', (string) $workspaceId)
 *               : null;
 *       }
 *   }
 */
interface ResolvesContext
{
    public function resolve(Request $request): ?AuthorizationContextInterface;
}
