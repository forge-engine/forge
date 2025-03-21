<?php
declare(strict_types=1);

namespace Forge\Core\View;

use Forge\Core\DI\Container;
use Forge\Core\View\View;

abstract class BaseComponent
{
    protected $props;

    public function __construct($props)
    {
        $this->props = $props;
    }

    abstract public function render(): mixed;

    protected function renderview(string $viewPath, array $data = [])
    {
        $view = new View(Container::getInstance());
        return $view->renderComponent($viewPath, $data);
    }
}
