<?php

declare(strict_types=1);

namespace App\View\Components\Navbar;

use Forge\Core\View\BaseComponent;
use Forge\Core\View\Component;

#[Component(name: "navbar", useDto: false)]
class Navbar extends BaseComponent
{
    public function render(): mixed
    {
        return $this->renderview("navbar/NavbarView");
    }
}
