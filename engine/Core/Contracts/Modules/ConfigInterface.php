<?php

namespace Forge\Core\Contracts\Modules;

interface ConfigInterface
{
 /**
  * @param array<int,mixed> $defaults
  */
 public function mergeModuleDefaults(array $defaults): void;

 /**
  * Load configuration
  */
 public function load(): void;

 /**
  * View config as an array of propertys
  *
  * @return array
  */
 public function toArray(): array;

 /**
  * Get configuration from file
  *
  * @param string $key
  * @param mixed default - If config not found set a default
  *
  * @return mixed
  */
 public function get(string $key, mixed $default = null): mixed;

 /**
  * Merge configurations
  *
  * @param string $key
  * @param mixed $value
  *
  * @return void
  */
 public function merge(string $key, mixed $value): void;

 /**
  * Set configuration property
  *
  * @param string $key
  * @param mixed $value
  *
  * @return void
  */
 public function set(string $key, mixed $value): void;
}