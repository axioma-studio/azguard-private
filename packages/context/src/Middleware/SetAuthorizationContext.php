<?php

declare(strict_types=1);

namespace AzGuard\Context\Middleware;

use AzGuard\Context\AuthorizationContextManager;
use AzGuard\Context\Contracts\ResolvesContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: azguard.context
 *
 * Перебирает зарегистрированные ResolvesContext-резолверы,
 * вызывает resolve($request) и устанавливает контекст в менеджер.
 *
 * Регистрация в bootstrap/app.php или RouteServiceProvider:
 *   ->withMiddleware(function (Middleware $m) {
 *       $m->alias(['azguard.context' => SetAuthorizationContext::class]);
 *   })
 *
 * Применение на роуте:
 *   Route::middleware('azguard.context')->group(function () { ... });
 */
final class SetAuthorizationContext
{
    /**
     * @param iterable<ResolvesContext> $resolvers
     */
    public function __construct(
        private readonly AuthorizationContextManager $manager,
        private readonly iterable $resolvers,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        foreach ($this->resolvers as $resolver) {
            $context = $resolver->resolve($request);

            if ($context !== null) {
                $this->manager->set($context);
            }
        }

        return $next($request);
    }
}
