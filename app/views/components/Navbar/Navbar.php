<?php

declare(strict_types=1);

namespace App\View\Components\Navbar;

use Forge\Core\View\BaseComponent;
use Forge\Core\View\Component;

#[Component(name: "nav-bar")]
class NavBar extends BaseComponent
{
    public function render(): mixed
    {
        return $this->renderview("NavBar/NavbarView");
    }
}
