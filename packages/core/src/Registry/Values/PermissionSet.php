<?php

declare(strict_types=1);

namespace AzGuard\Registry\Values;

use Closure;

/**
 * Immutable set of resolved permission keys for one user+panel.
 * Supports wildcard '*' (SuperAdmin) and patterns like 'app.documents.*'.
 *
 * ## Internal representation
 *
 * Keys are stored as an `array<string, true>` hash-map (`$index`) rather than
 * a `list<string>`, so that `has()` and `grants()` resolve in O(1) for exact
 * matches via `isset()` instead of O(n) via `in_array()`.
 *
 * `isWildcard()` is precomputed as a boolean flag on construction — no array
 * scan on every call.
 *
 * `keys()` / `toArray()` reconstruct the plain list on demand via
 * `array_keys($this->index)` — only pay the cost when serialising.
 */
final class PermissionSet
{
    /**
     * Hash-map for O(1) `isset()` lookups.
     *
     * @var array<string, true>
     */
    private array $index;

    /**
     * Precomputed flag — avoids scanning $index on every isWildcard() call.
     */
    private bool $wildcard;

    private function __construct(array $keys)
    {
        $unique          = array_unique($keys);
        $this->wildcard  = in_array('*', $unique, strict: true);
        // When the set is a wildcard we don't need to store anything else.
        $this->index     = $this->wildcard
            ? ['*' => true]
            : array_fill_keys($unique, true);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /** @param list<string> $keys */
    public static function fromKeys(array $keys): self
    {
        return new self($keys);
    }

    public static function wildcard(): self
    {
        return new self(['*']);
    }

    /**
     * Build a PermissionSet from raw keys returned by a GrantSource.
     *
     * Centralises the repeated empty / wildcard / fromKeys pattern
     * that previously appeared in every GrantSource implementation.
     *
     * @param  list<string>  $keys
     */
    public static function fromRawKeys(array $keys): self
    {
        if ($keys === []) {
            return self::empty();
        }

        // Delegate: constructor will set $this->wildcard = true when '*' present.
        return new self($keys);
    }

    /**
     * Merge two sets. If either is wildcard — result is wildcard.
     */
    public function merge(self $other): self
    {
        if ($this->wildcard || $other->wildcard) {
            return self::wildcard();
        }

        // Merge the two hash-maps directly — avoids array_unique on the union.
        $merged = $this->index + $other->index;

        $new          = new self([]);
        $new->index   = $merged;
        $new->wildcard = false;

        return $new;
    }

    /**
     * Exact key match — O(1).
     */
    public function has(string $key): bool
    {
        return $this->wildcard || isset($this->index[$key]);
    }

    /**
     * Wildcard pattern match: 'app.documents.*' covers 'app.documents.view'.
     *
     * Iterates only over keys that contain '*' — exact keys are skipped immediately.
     */
    public function matchesWildcard(string $key): bool
    {
        if ($this->wildcard) {
            return true;
        }

        foreach (array_keys($this->index) as $pattern) {
            if (! str_contains($pattern, '*')) {
                continue;
            }

            $regex = '/^'.str_replace(['\\.', '\\*'], ['[.]', '.*'], preg_quote($pattern, '/')).'$/';

            if (preg_match($regex, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Full check: exact match (O(1)) OR wildcard pattern.
     */
    public function grants(string $key): bool
    {
        return $this->has($key) || $this->matchesWildcard($key);
    }

    public function isWildcard(): bool
    {
        return $this->wildcard;
    }

    public function isEmpty(): bool
    {
        return $this->index === [];
    }

    /**
     * Filter keys (used for catalog validation).
     */
    public function filter(Closure $callback): self
    {
        $new          = new self([]);
        $new->index   = array_filter($this->index, fn (bool $_, string $k): bool => $callback($k), ARRAY_FILTER_USE_BOTH);
        $new->wildcard = isset($new->index['*']);

        return $new;
    }

    /**
     * Return keys as a plain list.
     *
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->index);
    }

    /**
     * Alias for {@see keys()} — kept for backwards compatibility.
     *
     * @deprecated Use keys() instead.
     * @return list<string>
     */
    public function toArray(): array
    {
        return $this->keys();
    }

    public function count(): int
    {
        return count($this->index);
    }
}
