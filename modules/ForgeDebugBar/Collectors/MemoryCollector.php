<?php

namespace Forge\Modules\ForgeDebugbar\Collectors;

class MemoryCollector implements CollectorInterface
{
    private int $startMemory;

    public function __construct()
    {
        $this->startMemory = memory_get_usage();
    }

    public static function collect(...$args): string
    {
        return self::instance()->getMemoryUsage();
    }

    public static function instance(): self
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    public function getMemoryUsage(): string
    {
        $memoryUsageBytes = round((memory_get_usage() - $this->startMemory) / 1024, 2);
        $memoryUsageMB = round($memoryUsageBytes / (1024 * 1024), 2);
        return $memoryUsageBytes . 'MB';
    }
}