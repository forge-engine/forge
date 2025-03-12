<?php
declare(strict_types=1);

use Forge\Core\DI\Container;
use Forge\Core\Http\Request;
use Forge\Core\View\Component;
use Forge\Core\View\View;

/**
 * Create a Request instance from globals.
 *
 * @return Request
 */
if (!function_exists("request")) {
    function request(): Request
    {
        return Request::createFromGlobals();
    }
}

/**
 * Create a Data Transfer Object (DTO).
 *
 * @template T of object
 * @param class-string<T> $class The DTO class name.
 * @param array<string, mixed> $data The data to populate the DTO.
 * @return object Returns a new DTO object.
 */
if (!function_exists("dto")) {
    function dto(string $class, array $data): object
    {
        return new $class(...$data);
    }
}

/**
 * Generate a URL for a named route.
 *
 * @param string $name The route name.
 * @param array<string, string|int> $params Route parameters.
 * @return string Returns the generated URL.
 */
if (!function_exists("route")) {
    function route(string $name, array $params = []): string
    {
        return "";
    }
}

/**
 * Render a view file.
 *
 * @param string $view The view file path (relative to views directory).
 * @param array<string, mixed> $data Data to pass to the view.
 * @return string Returns the rendered view content.
 */
if (!function_exists("view")) {
    function view(string $view, array $data = []): string
    {
        return (new View(Container::getInstance()))->render($view, $data);
    }
}

/**
 * Escape HTML entities in a string.
 *
 * @param string $value The string to escape.
 * @return string Returns the escaped string.
 */
if (!function_exists("e")) {
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
    }
}
/**
 * Output a value without escaping.
 *
 * @param mixed $value The value to output raw.
 * @return string Returns the raw string representation of the value.
 */
if (!function_exists("raw")) {
    function raw(mixed $value): string
    {
        return (string) $value;
    }
}

/**
 * Get the content of a defined section.
 *
 * @param string $name The name of the section.
 * @return string Returns the section content, or an empty string if not found.
 */
if (!function_exists("section")) {
    function section(string $name): string
    {
        return View::section($name);
    }
}

/**
 * End the current section.
 *
 * @return void
 */
if (!function_exists("endSection")) {
    function endSection(): void
    {
        View::endSection();
    }
}

/**
 * Set the layout to be used for the current view.
 *
 * @param string $name The name of the layout (without extension).
 * @return void
 */
if (!function_exists("layout")) {
    function layout(string $name): void
    {
        View::layout($name);
    }
}

/**
 * Render a component with props.
 *
 * @param string $name The name of the component (without extension).
 * @param array $props The props to pass to the component.
 * @return void
 */
if (!function_exists("component")) {
    function component(string $name, array $props = []): string
    {
        return Component::render($name, $props);
    }
}
