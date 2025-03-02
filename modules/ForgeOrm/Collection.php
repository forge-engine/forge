<?php

namespace Forge\Modules\ForgeOrm;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Forge\Core\Contracts\Modules\ForgeOrm\CollectionInterface as CoreCollectionInterface;
use Forge\Core\Models\Model;
use IteratorAggregate;
use JsonSerializable;

class Collection implements ArrayAccess, IteratorAggregate, Countable, JsonSerializable, CoreCollectionInterface
{
    /**
     * @var array<int, mixed> The items in the collection.
     */
    protected array $items;

    /**
     * Constructor.
     *
     * @param array<int, mixed> $items Initial items for the collection.
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Get the first item from the collection.
     *
     * @return mixed|null Returns the first item or null if the collection is empty.
     */
    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    /**
     * Get all items in the collection as a plain array.
     *
     * @return array<int, mixed>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Convert the collection to JSON.
     *
     * @param int $options JSON encoding options (optional).
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Magic method to convert the collection to string (JSON representation).
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * Get an iterator for the collection (for foreach loops).
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Determine if an item exists at an offset (ArrayAccess interface).
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * Get an item at a given offset (ArrayAccess interface).
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    /**
     * Set an item at a given offset (ArrayAccess interface).
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * Unset an item at a given offset (ArrayAccess interface).
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * Specify data which should be serialized to JSON (JsonSerializable interface).
     *
     * @return array<int, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Serialize the collection and its items for JSON output.
     *
     * This method iterates through the collection and calls serializeForJson()
     * on each Model item to get its serialized representation.
     *
     * @return array<int, array<string, mixed>>
     */
    public function serializeForJson(): array
    {
        return $this->map(function ($item) {
            if ($item instanceof Model) {
                return $item->serializeForJson();
            }
            return $item;
        })->toArray();
    }

    /**
     * Run a map over each of the items.
     *
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback): static
    {
        $results = array_map($callback, $this->items);
        return new static($results);
    }

    /**
     * Determine if the collection is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * Pluck a single column/key value from each item in the collection.
     *
     * @param string $key The key to pluck from each item.
     * @return array<int, mixed> An array of plucked values.
     */
    public function pluck(string $key): array
    {
        $results = [];
        foreach ($this->items as $item) {
            if (is_array($item) && array_key_exists($key, $item)) {
                $results[] = $item[$key];
            } elseif (is_object($item) && property_exists($item, $key)) {
                $results[] = $item->{$key};
            } else {
                $results[] = null; // Or handle missing key as needed (e.g., throw exception, skip item)
            }
        }
        return $results;
    }

}