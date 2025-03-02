<?php

namespace Forge\Core\Helpers;

use Forge\Core\DependencyInjection\Container;
use Forge\Modules\ForgeDebugbar\Collectors\ExceptionCollector;
use Forge\Modules\ForgeDebugbar\Collectors\MessageCollector;
use Forge\Modules\ForgeDebugbar\Collectors\TimelineCollector;
use ReflectionClass;

class Debug
{
    public function __construct()
    {
    }

    public static function pre(...$vars): void
    {
        echo "<pre>";
        foreach ($vars as $var) {
            print_r($var);
            echo '<br />';
        }
        echo "</pre></div>";
        die(1);
    }

    /**
     * Dump and die
     * Prints human readable information about one ore more variables and then exits.
     *
     * @param mixed
     *
     * @return void
     */
    public static function dd(...$vars): void
    {
        $config = App::config();
        $cssPaths = $config->get('debug.paths', []);

        $fileContent = '';
        if (!empty($cssPaths)) {
            $cssPath = $cssPaths[0];
            $fileContent = App::getFileContent(BASE_PATH . "/" . $cssPath);
        }

        if (empty($fileContent)) {
            $fileContent = "
                    .dd-container {
                    background-color: #18181A;
                    border: 1px solid #555;
                    border-radius: 4px;
                    padding: 15px;
                    font-family: 'Consolas', Courier, monospace;
                    font-size: 0.9rem;
                    line-height: 1.5;
                    color: #f8f8f2;
                    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
                    max-width: 100%;
                    overflow-x: auto;
                    overflow-y: auto;
                    max-height: 80vh;
                    }
                    .dd-container pre {
                    margin: 0;
                    white-space: pre-wrap;
                    word-wrap: break-word;
                    font-family: inherit;
                    font-size: inherit;
                    line-height: inherit;
                    color: inherit;
                    }
                    .dd-container .key {
                    color: #fff;
                    font-weight: 500;
                    }

                    .dd-container .string {
                    color: #3bdb3a;
                    }

                    .dd-container .number {
                    color: #e6b450;
                    }

                    .dd-container .boolean {
                    color: #61afef;
                    font-weight: 700;
                    }

                    .dd-container .null {
                    color: #ff8400;
                    }
                    .dd-container .object,
                    .dd-container .array,
                    .dd-container .object-class {
                    color: #61afef;
                    font-weight: bold;
                    }
                    .dd-container .object-property,
                    .dd-container .array-element {
                    margin-left: 15px;
                    display: block;
                    }
                    .dd-container .value {
                    font-weight: normal;
                    }
                    .dd-container .object-class {
                    font-style: italic;
                    color: #777;
                    }
                ";
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $callInfo = null;

        if (isset($backtrace[0])) {
            $call = $backtrace[0];
            $file = isset($call['file']) ? $call['file'] : 'unknown file';
            $line = isset($call['line']) ? $call['line'] : 'unknown line';

            $filePath = $file ?? null;
            if (strpos($filePath, BASE_PATH) === 0) {
                $filePath = substr($filePath, strlen(BASE_PATH));
            }

            $callInfo = "<div class='dd-trace'><code>dd() called from <span class='dd-trace-file'>" . htmlspecialchars($filePath) . "</span>:<span class='dd-trace-line'>" . $line . "</span></code></div>\n";
        }

        echo "<style>\n" . $fileContent . "\n</style>";
        echo "<div class='dd-container'>";

        if ($callInfo) {
            echo $callInfo;
        }

        echo "<pre>";
        foreach ($vars as $var) {
            echo self::formatVarForHtml($var);
        }
        echo "</pre></div>";
        die(1);
    }

    /**
     * Recursively formats a variable for HTML output with CSS classes.
     *
     * @param mixed $var The variable to format
     * @param int $indentationLevel Current indentation level (for nested structures)
     * @return string HTML markup for the variable
     */
    public static function formatVarForHtml($var, int $indentationLevel = 0): string
    {
        $output = '';
        $indent = str_repeat('  ', $indentationLevel);

        if (is_array($var)) {
            $output .= "<span class='array'>{</span>";
            if (!empty($var)) {
                $output .= "\n";
                foreach ($var as $key => $value) {
                    $output .= $indent . "  ";
                    $output .= "<span class='key'>" . htmlspecialchars(print_r($key, true)) . " =></span> ";
                    $output .= "<span class='value'>" . self::formatVarForHtml($value, $indentationLevel + 2) . "</span>,\n";
                }
                $output .= $indent;
            }
            $output .= "<span class='array'>}</span>";
        } elseif (is_object($var)) {
            $output .= "<span class='object'>Object</span> <span class='object-class'>(" . get_class($var) . ")</span> <span class='object'>{</span>";
            $reflection = new ReflectionClass($var);
            $properties = $reflection->getProperties();
            if (!empty($properties)) {
                $output .= "\n";
                foreach ($properties as $property) {
                    $property->setAccessible(true);
                    $value = $property->getValue($var);
                    $propertyName = $property->getName();
                    $output .= $indent . "  ";
                    $output .= "<span class='key'>" . htmlspecialchars($propertyName) . ":</span> ";
                    $output .= "<span class='value'>" . self::formatVarForHtml($value, $indentationLevel + 2) . "</span>,\n";
                }
                $output .= $indent;
            }
            $output .= "<span class='object'>}</span>";
        } elseif (is_string($var)) {
            $output .= "<span class='string'>\"" . htmlspecialchars($var) . "\"</span>";
        } elseif (is_int($var) || is_float($var)) {
            $output .= "<span class='number'>" . htmlspecialchars($var) . "</span>";
        } elseif (is_bool($var)) {
            $output .= "<span class='boolean'>" . ($var ? 'true' : 'false') . "</span>";
        } elseif (is_null($var)) {
            $output .= "<span class='null'>null</span>";
        } else {
            $output .= "<span class='unknown'>" . htmlspecialchars(print_r($var, true)) . "</span>";
        }

        return $output;
    }

    /**
     * Log messages to the debugbar
     *
     * @param mixed $message
     * @param string $label
     *
     * @return void
     */
    public static function message(mixed $message, string $label = 'info'): void
    {
        if (class_exists(MessageCollector::class)) {
            if (filter_var($_ENV["FORGE_APP_DEBUG"] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                /** @var MessageCollector $messageCollector */
                $messageCollector = Container::getContainer()->get(MessageCollector::class);
                $messageCollector::instance()->addMessage($message, $label);
            }
        }
    }

    public static function exceptionCollector(\Throwable $exception): void
    {
        if (class_exists(ExceptionCollector::class)) {
            if (filter_var($_ENV['FORGE_APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                /** @var ExceptionCollector $exceptionCollector */
                $exceptionCollector = Container::getContainer()->get(ExceptionCollector::class);
                $exceptionCollector::instance()->addException($exception);
            }
        }
    }

    /**
     * Track new event during request lifecycle
     *
     * @param string $name
     * @param string $label
     * @param array $data
     *
     * @return void
     */
    public static function addEvent(string $name, string $label, array $data = []): void
    {

        if (class_exists(TimelineCollector::class)) {
            if (filter_var($_ENV["FORGE_APP_DEBUG"] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                /** @var TimelineCollector $timelineCollector */
                $timelineCollector = Container::getContainer()->get(TimelineCollector::class);
                $timelineCollector::instance()->addEvent($name, $label, $data);
            }
        }

    }

    public static function backtraceOrigin(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);

        $origin = 'Unknown Origin';

        foreach ($trace as $frame) {
            if (isset($frame['class']) && $frame['class'] !== self::class) {
                if (strpos($frame['class'], 'Forge\\Modules\\ForgeDatabase\\Adapters') === 0) {
                    continue;
                }
                if (strpos($frame['class'], 'Forge\\Modules\\ForgeDebugbar') === 0) {
                    continue;
                }

                $class = $frame['class'] ?? 'unknown class';
                $function = $frame['function'] ?? 'unknown function';
                $origin = "{$class}@{$function}";
            }
        }
        return $origin;
    }

}