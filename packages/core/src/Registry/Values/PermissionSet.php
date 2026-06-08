<?php

declare(strict_types=1);

namespace AzGuard\Registry\Values;

/**
 * Иммутабельный набор resolved permission keys для одного пользователя+панель.
 * Поддерживает wildcard '*' (SuperAdmin) и паттерны 'app.documents.*'.
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
     * Слияние двух наборов. Если хоть один wildcard — результат wildcard.
     */
    public function merge(self $other): self
    {
        if ($this->isWildcard() || $other->isWildcard()) {
            return self::wildcard();
        }

        return new self([...$this->keys, ...$other->keys]);
    }

    /**
     * Точное совпадение ключа.
     */
    public function contains(string $key): bool
    {
        if ($this->isWildcard()) {
            return true;
        }

        return in_array($key, $this->keys, strict: true);
    }

    /**
     * Wildcard-совпадение: 'app.documents.*' покрывает 'app.documents.view'.
     * Логика аналогична текущей HasAzGuard::hasAzPermission().
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
     * Полная проверка: точное совпадение OR wildcard-паттерн.
     */
    public function grants(string $key): bool
    {
        return $this->contains($key) || $this->matchesWildcard($key);
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
     * Фильтрация ключей (используется для валидации через каталог).
     */
    public function filter(\Closure $callback): self
    {
        return new self(array_filter($this->keys, $callback));
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
