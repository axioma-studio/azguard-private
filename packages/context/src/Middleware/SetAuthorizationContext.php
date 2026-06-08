<?php

declare(strict_types=1);

namespace AzGuard\Context\Middleware;

use AzGuard\Context\AuthorizationContextManager;
use AzGuard\Context\Contracts\ResolvesContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: устанавливает AuthorizationContext на время запроса.
 *
 * Использует ResolvesContext (настраивается через 'context_resolver' в конфиге)
 * для автоматического определения контекста из запроса.
 *
 * Регистрация:
 *   // bootstrap/app.php (Laravel 11+)
 *   ->withMiddleware(function (Middleware $middleware) {
 *       $middleware->alias(['azguard.context' => SetAuthorizationContext::class]);
 *   })
 *
 *   // Route
 *   Route::middleware('azguard.context')->group(function () { ... });
 *
 * Или вручную (без резолвера):
 *   app(AuthorizationContextManager::class)
 *       ->setContext(AuthorizationContext::make('workspace', $id));
 */
final class SetAuthorizationContext
{
    public function __construct(
        private readonly AuthorizationContextManager $manager,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $resolverClass = config('az-guard-context.context_resolver');

        if ($resolverClass !== null) {
            /** @var ResolvesContext $resolver */
            $resolver = app($resolverClass);
            $context = $resolver->resolve($request);

            if ($context !== null) {
                $this->manager->setContext($context);
            }
        }

        $response = $next($request);

        // Очищаем контекст после запроса (защита от утечки в long-running processes).
        $this->manager->clearContext();

        return $response;
    }
}
