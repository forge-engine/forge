<?php

use App\Modules\ForgeDebugbar\Collectors\TimelineCollector;
use App\Modules\ForgeDebugbar\Collectors\ViewCollector;
use App\Modules\ForgeDebugbar\DebugBar;
use Forge\Core\DI\Container;
use Forge\Exceptions\MissingServiceException;
use Forge\Exceptions\ResolveParameterException;

if (!function_exists("add_timeline_event")) {
    /**
     * @throws ReflectionException
     * @throws MissingServiceException
     * @throws ResolveParameterException
     */
    function add_timeline_event(string $name, string $label, array $data = []): void
    {
            if (filter_var($_ENV["APP_DEBUG"] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                /** @var TimelineCollector $timelineCollector */
                $timelineCollector = Container::getInstance()->get(TimelineCollector::class);
                $timelineCollector::instance()->addEvent($name, $label, $data);
            }
    }
}

if (!function_exists("collect_view_data")) {
    function collect_view_data(string $view, mixed $data = []): void
    {
        ViewCollector::instance()->addView($view, $data);
    }
}