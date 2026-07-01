<?php

declare(strict_types=1);

namespace AzGuard\Registry\Matching;

use AzGuard\Contracts\PermissionMatcher;
use Override;

/**
 * Default wildcard matcher preserving AzGuard's historical grammar: '*' expands
 * to '.*', so it crosses dot boundaries ('app.*' also matches 'app.a.b'). This
 * is the 0.3.0 default; the stricter {@see HierarchicalPermissionMatcher} is
 * opt-in via config('az-guard.matcher') and becomes the default in 0.4.0 (with
 * this class as the `legacy_wildcard` opt-out).
 */
final class WildcardPermissionMatcher implements PermissionMatcher
{
    /** @var array<string, string> Compiled regex keyed by pattern (memoized). */
    private array $compiled = [];

    #[Override]
    public function matches(string $pattern, string $key): bool
    {
        return preg_match($this->regexFor($pattern), $key) === 1;
    }

    private function regexFor(string $pattern): string
    {
        return $this->compiled[$pattern] ??= '/^'.str_replace(
            ['\\.', '\\*'],
            ['[.]', '.*'],
            preg_quote($pattern, '/'),
        ).'$/';
    }
}
