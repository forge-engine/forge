<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Services;

use Forge\Core\Cache\CacheManager;
use Forge\Core\DI\Attributes\Service;

#[Service]
final class CacheService
{
  public function __construct(
    private readonly CacheManager $cacheManager
  ) {
  }

  public function getStats(): array
  {
    return [
      'driver' => $this->getDriverName(),
      'keys_count' => $this->getKeysCount(),
    ];
  }

  public function clearAll(): void
  {
    $this->cacheManager->clear();
  }

  public function clearTag(string $tag): void
  {
    $this->cacheManager->clearTag($tag);
  }

  private function getDriverName(): string
  {
    try {
      $reflection = new \ReflectionClass($this->cacheManager);
      $driverProperty = $reflection->getProperty('driver');
      $driverProperty->setAccessible(true);
      $driver = $driverProperty->getValue($this->cacheManager);

      return $driver ? get_class($driver) : 'Unknown';
    } catch (\ReflectionException) {
      return 'Unknown';
    }
  }

  private function getKeysCount(): int
  {
    try {
      $reflection = new \ReflectionClass($this->cacheManager);
      $driverProperty = $reflection->getProperty('driver');
      $driverProperty->setAccessible(true);
      $driver = $driverProperty->getValue($this->cacheManager);

      if ($driver && method_exists($driver, 'keys')) {
        $keys = $driver->keys();
        return is_array($keys) ? count($keys) : 0;
      }
    } catch (\ReflectionException) {
    }

    return 0;
  }
}
