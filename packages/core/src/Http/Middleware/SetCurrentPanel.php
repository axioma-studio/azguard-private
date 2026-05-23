<?php

declare(strict_types=1);

namespace AzGuard\Http\Middleware;

use AzGuard\Facades\AzGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetCurrentPanel
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $panelId): Response
    {
        $panel = AzGuard::panel(id: $panelId);

        if ($panel === null) {
            abort(code: 500, message: "AzGuard panel [{$panelId}] is not registered.");
        }

        AzGuard::setCurrentPanel(panel: $panel);

        try {
            return $next($request);
        } finally {
            AzGuard::setCurrentPanel(panel: null);
        }
    }
}
