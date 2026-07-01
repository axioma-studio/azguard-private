<?php

declare(strict_types=1);

namespace AzGuard\Support;

/**
 * Per-request scratch state for AzGuard. Registered as a `scoped` binding, so the
 * container flushes it at the start of each request/job — Octane-safe by design.
 *
 * Used for "do this at most once per request" side effects (e.g. a diagnostic
 * warning) without the cross-request bleed a `static` flag would cause under
 * Octane (a static warns once per worker, never again).
 *
 * @internal
 */
final class RequestState
{
    /** @var array<string, true> */
    private array $seen = [];

    public function once(string $key, callable $callback): void
    {
        if (isset($this->seen[$key])) {
            return;
        }

        $this->seen[$key] = true;

        $callback();
    }
}
