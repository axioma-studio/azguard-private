<?php

declare(strict_types=1);

namespace AzGuard\Auth;

use AzGuard\Facades\AzGuard;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Gate-политика для проверки direct grants через Laravel Gate.
 *
 * Регистрируется автоматически сервис-провайдером:
 *   Gate::define('direct-grant', [DirectGrantPolicy::class, 'check']);
 *
 * Использование:
 *
 *   // Проверка с текущей панелью
 *   Gate::allows('direct-grant', 'app.documents.export');
 *
 *   // Проверка с явной панелью
 *   Gate::allows('direct-grant', ['app.documents.export', 'app']);
 *
 *   // В политике
 *   public function export(User $user, Document $document): bool
 *   {
 *       return Gate::allows('direct-grant', 'app.documents.export');
 *   }
 */
final class DirectGrantPolicy
{
    /**
     * @param  Authenticatable      $user
     * @param  string|array<mixed>  $arguments  Ключ или [ключ, панель]
     */
    public function check(Authenticatable $user, string|array $arguments): bool
    {
        if (! method_exists($user, 'hasDirectGrant')) {
            return false;
        }

        [$permissionKey, $panelId] = $this->parseArguments($arguments);

        return $user->hasDirectGrant($permissionKey, $panelId);
    }

    /**
     * @param  string|array<mixed>  $arguments
     * @return array{0: string, 1: string|null}
     */
    private function parseArguments(string|array $arguments): array
    {
        if (is_string($arguments)) {
            return [$arguments, AzGuard::currentPanel()?->getId()];
        }

        $key   = (string) ($arguments[0] ?? '');
        $panel = isset($arguments[1]) ? (string) $arguments[1] : AzGuard::currentPanel()?->getId();

        return [$key, $panel];
    }
}
