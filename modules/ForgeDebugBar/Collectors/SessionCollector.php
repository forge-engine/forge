<?php

namespace Forge\Modules\ForgeDebugbar\Collectors;

use Forge\Core\DependencyInjection\Container;
use Forge\Http\Session;

class SessionCollector implements CollectorInterface
{
    public static function collect(...$args): array
    {
        return self::instance()->collectSessionData();
    }

    public static function instance(): self
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }
        return $instance;
    }

    public function collectSessionData(): array
    {
        $container = Container::getContainer();
        if ($container->has(Session::class)) {
            /** @var Session $session */
            $session = $container->get(Session::class);
            $session->start();

            $sessionData = $_SESSION;

            $formattedSessionData = [];
            foreach ($sessionData as $key => $value) {
                $formattedSessionData[$key] = $this->formatValue($value);
            }
            return $formattedSessionData;
        } else {
            return [['error' => 'Session class not bound in Container']];
        }
    }

    private function formatValue($value): string
    {
        if (is_object($value)) {
            return 'Object (' . get_class($value) . ')';
        } elseif (is_array($value)) {
            return 'Array (' . count($value) . ' items)';
        } elseif (is_resource($value)) {
            return 'Resource (' . get_resource_type($value) . ')';
        } else {
            return (string)$value;
        }
    }
}