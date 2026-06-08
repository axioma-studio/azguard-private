<?php

declare(strict_types=1);

namespace AzGuard\Http\Middleware;

use AzGuard\Facades\AzGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware: проверяет наличие direct grant у текущего пользователя.
 *
 * Подключение в маршрутах:
 *
 *   Route::get('/export', ExportController::class)
 *       ->middleware('az.grant:app.documents.export,app');
 *
 * Если второй аргумент (panel) опущен, используется текущая панель AzGuard,
 * а если текущая панель не установлена — null (проверка без фильтра панели).
 *
 * Ответы:
 *   401 — пользователь не аутентифицирован
 *   403 — grant отсутствует или истёк
 */
final class CheckDirectGrant
{
    /**
     * @param  Closure(Request): Response  $next
     * @param  string                      $permissionKey  Ключ разрешения
     * @param  string|null                 $panelId        Идентификатор панели (опционально)
     */
    public function handle(
        Request    $request,
        Closure    $next,
        string     $permissionKey,
        ?string    $panelId = null,
    ): Response {
        abort_if(
            boolean: ! $request->user(),
            code:    Response::HTTP_UNAUTHORIZED,
            message: 'Unauthenticated.',
        );

        $resolvedPanel = $panelId ?? AzGuard::currentPanel()?->getId();

        $user = $request->user();

        $hasGrant = method_exists($user, 'hasDirectGrant')
            ? $user->hasDirectGrant($permissionKey, $resolvedPanel)
            : false;

        abort_if(
            boolean: ! $hasGrant,
            code:    Response::HTTP_FORBIDDEN,
            message: "Direct grant [{$permissionKey}] требуется для данного действия.",
        );

        return $next($request);
    }
}
