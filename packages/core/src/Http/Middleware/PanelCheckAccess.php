<?php

declare(strict_types=1);

namespace AzGuard\Http\Middleware;

use AzGuard\Facades\AzGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Combined middleware: sets the current panel then verifies a permission.
 *
 * Usage:
 *   Route::middleware('azguard.panel_check:admin,admin.posts.edit')
 *
 * Equivalent to chaining azguard.panel:admin + manual hasPermission check.
 * Always resets the current panel after the request (even on exception).
 */
final class PanelCheckAccess
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $panelId, string $permission): Response
    {
        $panel = AzGuard::panel(id: $panelId);

        if ($panel === null) {
            abort(code: 500, message: "AzGuard panel [{$panelId}] is not registered.");
        }

        AzGuard::setCurrentPanel(panel: $panel);

        try {
            $user = $request->user();

            if ($user === null || ! method_exists($user, 'hasPermission') || ! $user->hasPermission($permission, $panelId)) {
                abort(403);
            }

            return $next($request);
        } finally {
            AzGuard::setCurrentPanel(panel: null);
        }
    }
}
