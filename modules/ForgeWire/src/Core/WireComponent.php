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

    protected function view(string $path, array|object $data = [], bool $loadFromModule = false): string
    {
        collect_view_data($path, $data);
        $v = new View(Container::getInstance());
        return $v->renderComponent($path, $data);
    }

    #[Action]
    public function input(...$keys): void
    {
    }
}
