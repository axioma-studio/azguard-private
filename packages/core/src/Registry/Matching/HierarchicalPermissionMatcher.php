<?php

declare(strict_types=1);

namespace AzGuard\Registry\Matching;

use AzGuard\Contracts\PermissionMatcher;
use Override;

/**
 * Hierarchical wildcard grammar that respects dot boundaries:
 *   '*'  matches within ONE segment ([^.]*) — 'a.*' matches 'a.b' but NOT 'a.b.c'
 *   '**' matches recursively across segments (.*) — 'a.**' matches 'a.b.c'
 *
 * Opt-in in 0.3.0 via config('az-guard.matcher'); the default remains
 * {@see WildcardPermissionMatcher} so existing behaviour is byte-for-byte
 * unchanged. 0.4.0 flips the default here.
 */
final class HierarchicalPermissionMatcher implements PermissionMatcher
{
    /** Collision-free placeholder reserving '**' before single '*' is expanded. */
    private const string DOUBLE_STAR = "\x00DS\x00";

    /** @var array<string, string> Compiled regex keyed by pattern (memoized). */
    private array $compiled = [];

    #[Override]
    public function matches(string $pattern, string $key): bool
    {
        return preg_match($this->regexFor($pattern), $key) === 1;
    }

    private function regexFor(string $pattern): string
    {
        return $this->compiled[$pattern] ??= $this->compile($pattern);
    }

    private function compile(string $pattern): string
    {
        // Order matters: reserve '**' (recursive) before single '*' (one segment).
        $regex = str_replace(
            ['\\*\\*', '\\*', '\\.'],
            [self::DOUBLE_STAR, '[^.]*', '[.]'],
            preg_quote($pattern, '/'),
        );

        $regex = str_replace(self::DOUBLE_STAR, '.*', $regex);

        return '/^'.$regex.'$/';
    }
}
