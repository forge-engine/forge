<?php

namespace Forge\Modules\ForgeDebugbar\Collectors;

use Forge\Core\Helpers\Path;

class ExceptionCollector implements CollectorInterface
{
    private array $exceptions = [];

    public static function collect(...$args): array
    {
        return self::instance()->exceptions;
    }

    public static function instance(): self
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    public function addException(\Throwable $exception): void
    {
        $this->exceptions[] = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => Path::filePath($exception->getFile()) . ':' . $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];
    }
}