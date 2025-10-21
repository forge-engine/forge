<?php

namespace App\Modules\ForgeWire\Core;

use App\Modules\ForgeWire\Attributes\Action;
use Forge\Core\DI\Container;
use Forge\Core\View\View;

abstract class WireComponent
{
    public function mount(array $props = []): void
    {
    }

    abstract public function render(): string;

    #[Action]
    public function input(...$keys): void
    {
    }

    protected function view(string $path, array|object $data = [], bool $loadFromModule = false): string
    {
        $v = new View(Container::getInstance());
        return $v->renderComponentView($path, $data);
    }
}
