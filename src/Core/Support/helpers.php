<?php
declare(strict_types=1);


use Forge\Core\DI\Container;
use Forge\Core\Http\Request;
use Forge\Core\View\View;

function request(): Request
{
    return Request::createFromGlobals();
}

function dto(string $class, array $data): object
{
    return new $class(...$data);
}

function route(string $name, array $params = []): string
{
    return '';
}

function view(string $view, array $data = []): string
{
    return (new View(Container::getInstance()))->render($view, $data);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function raw(mixed $value): string
{
    return (string)$value;
}

function section(string $name): string
{
    return View::section($name);
}

function layout(string $name): void
{
    View::layout($name);
}
