<?php

declare(strict_types=1);

namespace AzGuard\Registry\Values;

/**
 * Immutable set of resolved permission keys for one user+panel.
 * Supports wildcard '*' (SuperAdmin) and patterns like 'app.documents.*'.
 */
final class PermissionSet
{
    /** @var list<string> */
    private readonly array $keys;

    private function __construct(array $keys)
    {
        $this->keys = array_values(array_unique($keys));
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
     * @param list<string> $keys
     */
    public static function fromRawKeys(array $keys): self
    {
        if ($keys === []) {
            return self::empty();
        }

        if (in_array('*', $keys, strict: true)) {
            return self::wildcard();
        }

        return self::fromKeys($keys);
    }

    /**
     * Merge two sets. If either is wildcard — result is wildcard.
     */
    public function merge(self $other): self
    {
        if ($this->isWildcard() || $other->isWildcard()) {
            return self::wildcard();
        }

        return new self([...$this->keys, ...$other->keys]);
    }

    /**
     * Exact key match.
     */
    public function has(string $key): bool
    {
        if ($this->isWildcard()) {
            return true;
        }

        return in_array($key, $this->keys, strict: true);
    }

    /**
     * Wildcard match: 'app.documents.*' covers 'app.documents.view'.
     */
    public function matchesWildcard(string $key): bool
    {
        if ($this->isWildcard()) {
            return true;
        }

        foreach ($this->keys as $pattern) {
            if (! str_contains($pattern, '*')) {
                continue;
            }

            $regex = '/^' . str_replace(['\\.', '\\*'], ['[.]', '.*'], preg_quote($pattern, '/')) . '$/';

            if (preg_match($regex, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Full check: exact match OR wildcard pattern.
     */
    public function grants(string $key): bool
    {
        return $this->has($key) || $this->matchesWildcard($key);
    }

    public function isWildcard(): bool
    {
        return in_array('*', $this->keys, strict: true);
    }

    public function isEmpty(): bool
    {
        return $this->keys === [];
    }

    /**
     * Filter keys (used for catalog validation).
     */
    public function filter(\Closure $callback): self
    {
        return new self(array_filter($this->keys, $callback));
    }

    /** @return list<string> */
    public function keys(): array
    {
        return $this->keys;
    }

    /** @return list<string> */
    public function toArray(): array
    {
        return $this->keys;
    }

    public function count(): int
    {
        return count($this->keys);
    }
}
