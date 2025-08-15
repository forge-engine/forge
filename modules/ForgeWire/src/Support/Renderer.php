<?php

namespace App\Modules\ForgeWire\Support;

use App\Modules\ForgeWire\Core\WireComponent;
use Forge\Core\View\View;

final class Renderer
{
    public function __construct(private View $view)
    {
    }

    public function render(WireComponent $instance, string $id, string $class): string
    {
        $inner = $instance->render();

        return raw(
            '<div wire:id="'.htmlspecialchars($id, ENT_QUOTES).'" wire:component="'.htmlspecialchars($class, ENT_QUOTES).'">' .
                $inner .
            '</div>'
        );
    }
}
