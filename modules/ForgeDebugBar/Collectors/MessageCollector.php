<?php

namespace Forge\Modules\ForgeDebugbar\Collectors;

class MessageCollector implements CollectorInterface
{
    private array $messages = [];

    public static function collect(...$args): array
    {
        return self::instance()->messages;
    }

    public static function instance(): self
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }
        return $instance;
    }

    public function addMessage(mixed $message, string $label = 'info'): void
    {
        $this->messages[] = [
            'message' => $message,
            'label' => $label,
            'time' => microtime(true),
        ];
    }
}