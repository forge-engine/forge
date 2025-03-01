<?php

namespace Forge\Modules\ForgeOrm;

use ArrayIterator;

interface CollectionInterface
{

    /**
     * Get the first item from the collection.
     *
     * @return mixed|null Returns the first item or null if the collection is empty.
     */
    public function first(): mixed;

    /**
     * Get all items in the collection as a plain array.
     *
     * @return array<int, mixed>
     */
    public function toArray(): array;

    /**
     * Convert the collection to JSON.
     *
     * @param int $options JSON encoding options (optional).
     * @return string
     */
    public function toJson(int $options = 0): string;

    /**
     * Magic method to convert the collection to string (JSON representation).
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * Get an iterator for the collection (for foreach loops).
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator;

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count(): int;

    /**
     * Determine if an item exists at an offset (ArrayAccess interface).
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool;

    /**
     * Get an item at a given offset (ArrayAccess interface).
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed;

    /**
     * Set an item at a given offset (ArrayAccess interface).
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void;

    /**
     * Unset an item at a given offset (ArrayAccess interface).
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void;

    /**
     * Specify data which should be serialized to JSON (JsonSerializable interface).
     *
     * @return array<int, mixed>
     */
    public function jsonSerialize(): array;

    /**
     * Serialize the collection and its items for JSON output.
     *
     * This method iterates through the collection and calls serializeForJson()
     * on each Model item to get its serialized representation.
     *
     * @return array<int, array<string, mixed>>
     */
    public function serializeForJson(): array;

    /**
     * Run a map over each of the items.
     *
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback): static;

    /**
     * Determine if the collection is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Pluck a single column/key value from each item in the collection.
     *
     * @param string $key The key to pluck from each item.
     * @return array<int, mixed> An array of plucked values.
     */
    public function pluck(string $key): array;

}