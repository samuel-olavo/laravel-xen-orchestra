<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Client;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * A deliberately small ordered list returned by resource queries.
 *
 * Illuminate's Collection would be nicer, but the core of this package does not
 * get to know that Laravel exists. Inside a Laravel app you can always call
 * `collect($result->all())` and carry on.
 *
 * @template TValue
 *
 * @implements IteratorAggregate<int, TValue>
 * @implements ArrayAccess<int, TValue>
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /** @param list<TValue> $items */
    public function __construct(protected array $items = [])
    {
    }

    /** @return list<TValue> */
    public function all(): array
    {
        return $this->items;
    }

    /** @return TValue|null */
    public function first(?callable $callback = null): mixed
    {
        foreach ($this->items as $item) {
            if ($callback === null || $callback($item)) {
                return $item;
            }
        }

        return null;
    }

    /** @return TValue|null */
    public function last(): mixed
    {
        return $this->items === [] ? null : $this->items[array_key_last($this->items)];
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /** @return static<mixed> */
    public function map(callable $callback): static
    {
        return new static(array_values(array_map($callback, $this->items)));
    }

    /** @return static<TValue> */
    public function filter(callable $callback): static
    {
        return new static(array_values(array_filter($this->items, $callback)));
    }

    /**
     * Pull a single attribute out of every item.
     *
     * @return list<mixed>
     */
    public function pluck(string $key): array
    {
        return array_map(
            static fn ($item) => is_array($item) ? ($item[$key] ?? null) : $item->get($key),
            $this->items,
        );
    }

    /** @return array<array-key, TValue> */
    public function keyBy(string $key): array
    {
        $keyed = [];

        foreach ($this->items as $item) {
            $value = is_array($item) ? ($item[$key] ?? null) : $item->get($key);

            if ($value !== null) {
                $keyed[$value] = $item;
            }
        }

        return $keyed;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;

            return;
        }

        $this->items[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
        $this->items = array_values($this->items);
    }

    public function toArray(): array
    {
        return array_map(
            static fn ($item) => is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : $item,
            $this->items,
        );
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
