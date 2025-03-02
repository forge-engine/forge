<?php

namespace Forge\Core\Contracts\Modules\ForgeOrm;

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
  * Run a map over each of the items.
  *
  * @param callable $callback
  * @return static
  */
 public function map(callable $callback): static;
}
