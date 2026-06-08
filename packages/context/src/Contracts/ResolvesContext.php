<?php

declare(strict_types=1);

namespace AzGuard\Context\Contracts;

use AzGuard\Context\AuthorizationContext;
use Illuminate\Http\Request;

/**
 * Интерфейс для классов, умеющих извлекать AuthorizationContext из Request.
 *
 * Реализуется в приложении:
 *
 *   final class WorkspaceContextResolver implements ResolvesContext
 *   {
 *       public function resolve(Request $request): ?AuthorizationContext
 *       {
 *           $id = $request->route('workspace');
 *           return $id
 *               ? new AuthorizationContext('app', 'workspace', $id)
 *               : null;
 *       }
 *
 *       public function panel(): string { return 'app'; }
 *   }
 *
 * Регистрируется в PanelProvider:
 *   AzGuardContext::registerResolver(WorkspaceContextResolver::class);
 */
interface ResolvesContext
{
    /**
     * Извлечь контекст из текущего request.
     * Вернуть null, если контекст неприменим к этому запросу.
     */
    public function resolve(Request $request): ?AuthorizationContext;

    /**
     * Панель, для которой работает этот resolver.
     */
    public function panel(): string;
}
